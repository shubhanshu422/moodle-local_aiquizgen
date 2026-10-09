<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_aiquizgen\local;

/**
 * Calls the configured AI provider and validates source-grounded responses.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_client {
    /** @var array Testable provider overrides; empty during normal operation. */
    private array $overrides;

    /**
     * Creates a provider client.
     *
     * @param array $overrides Optional endpoint/model/key/timeout overrides for isolated tests.
     */
    public function __construct(array $overrides = []) {
        $this->overrides = $overrides;
    }

    /**
     * Sends a minimal provider connectivity test.
     *
     * @return array Provider response and usage data.
     */
    public function test_connection(): array {
        $apikey = $this->setting('apikey');
        if (!$apikey) {
            throw new \moodle_exception('missingapikey', 'local_aiquizgen');
        }
        return $this->request_chunk([[
            'reference' => 'Connection test',
            'cmid' => 0,
            'text' => 'Moodle is a learning management system.',
        ]], 1, 'easy', 'English', $apikey);
    }

    /**
     * Generates and validates questions across source chunks.
     *
     * @param array $documents Extracted course documents.
     * @param int $count Maximum question count.
     * @param string $difficulty Requested difficulty.
     * @param string $language Output language.
     * @return array Validated questions and usage data.
     */
    public function generate(array $documents, int $count, string $difficulty, string $language): array {
        $apikey = $this->setting('apikey');
        if (!$apikey) {
            throw new \moodle_exception('missingapikey', 'local_aiquizgen');
        }
        if (!$documents) {
            throw new \moodle_exception('insufficientcontent', 'local_aiquizgen');
        }

        $chunks = $this->build_chunks($documents, max(2000, (int)($this->setting('chunksize') ?: 12000)));
        $allquestions = [];
        $inputtokens = 0;
        $outputtokens = 0;
        $apicalls = 0;
        $lasterror = null;

        foreach ($chunks as $index => $chunk) {
            $remaining = max(0, $count - count($allquestions));
            if ($remaining === 0) {
                break;
            }
            $chunksleft = count($chunks) - $index;
            $chunkcount = max(1, (int)ceil($remaining / $chunksleft));
            try {
                $result = $this->request_chunk($chunk, $chunkcount, $difficulty, $language, $apikey);
            } catch (\Throwable $exception) {
                $lasterror = $exception;
                continue;
            }
            $allquestions = array_merge($allquestions, $result['questions']);
            $inputtokens += $result['inputtokens'];
            $outputtokens += $result['outputtokens'];
            $apicalls++;
        }

        $questions = $this->validate_questions($allquestions, $documents, $count);
        if (!$questions) {
            if ($lasterror) {
                throw $lasterror;
            }
            throw new \moodle_exception('insufficientcontent', 'local_aiquizgen');
        }

        return [
            'questions' => $questions,
            'inputtokens' => $inputtokens,
            'outputtokens' => $outputtokens,
            'apicalls' => $apicalls,
            'model' => (string)$this->setting('model') ?: 'unknown',
            'chunks' => count($chunks),
        ];
    }

    /**
     * Sends one source chunk to the configured provider.
     *
     * @param array $documents Source documents in this chunk.
     * @param int $count Requested questions.
     * @param string $difficulty Requested difficulty.
     * @param string $language Output language.
     * @param string $apikey Provider API key.
     * @return array Decoded provider questions and usage data.
     */
    private function request_chunk(array $documents, int $count, string $difficulty, string $language, string $apikey): array {
        global $CFG;
        $source = '';
        foreach ($documents as $document) {
            $source .= "\n[SOURCE: {$document['reference']}]\n{$document['text']}";
        }
        $schema = '{"questions":[{"question":"...",' .
            '"options":["...","...","...","..."],"correct_answer":0,' .
            '"explanation":"...","source_reference":"exact SOURCE label",' .
            '"source_evidence":"short verbatim excerpt from that source"}]}';
        $prompt = "Generate up to {$count} {$difficulty} MCQs in {$language}. " .
            'Generate questions ONLY from the supplied SOURCE CONTENT. ' .
            'Do not use outside knowledge. If this content does not contain enough information ' .
            'for a valid question, return fewer questions. Never invent or infer missing information. ' .
            'Every question, option, correct answer, and explanation must be directly supported by the named source. ' .
            'For source_evidence, copy a short supporting excerpt verbatim from that exact source. ' .
            "Return JSON only using this schema: {$schema}. " .
            "correct_answer is a zero-based index.\nSOURCE CONTENT:\n{$source}";

        require_once($CFG->libdir . '/filelib.php');
        $requestbody = json_encode([
            'model' => $this->setting('model'),
            'temperature' => 0.1,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => 'You create strictly source-grounded Moodle assessment questions.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);
        $attempt = 0;
        do {
            $attempt++;
            $curl = new \curl();
            $raw = $curl->post($this->setting('endpoint'), $requestbody, [
                'CURLOPT_HTTPHEADER' => ['Authorization: Bearer ' . $apikey, 'Content-Type: application/json'],
                'CURLOPT_CONNECTTIMEOUT' => (int)($this->overrides['connecttimeout'] ?? 15),
                'CURLOPT_TIMEOUT' => (int)($this->overrides['timeout'] ?? 120),
            ]);
            $httpcode = (int)($curl->get_info()['http_code'] ?? 0);
            $payload = json_decode($raw, true);
            $retryable = $httpcode === 0 || $httpcode === 429 || $httpcode >= 500;
            if ($httpcode >= 200 && $httpcode < 300 && !isset($payload['error'])) {
                break;
            }
            if (!$retryable || $attempt >= 3) {
                $message = clean_param($payload['error']['message'] ?? ('HTTP ' . $httpcode), PARAM_TEXT);
                $message = str_replace($apikey, '[redacted]', $message);
                $message = preg_replace('/Bearer\s+[^\s]+/i', 'Bearer [redacted]', $message);
                $message = \core_text::substr($message, 0, 500);
                throw new \moodle_exception('aiapierror', 'local_aiquizgen', '', $message);
            }
            $delay = (int)($this->overrides['retrydelay'] ?? $attempt);
            if ($delay > 0) {
                sleep($delay);
            }
        } while ($attempt < 3);
        $content = $payload['choices'][0]['message']['content'] ?? '';
        $decoded = json_decode($content, true);
        $questions = $decoded['questions'] ?? $decoded;
        if (!is_array($questions)) {
            throw new \moodle_exception('invalidairesponse', 'local_aiquizgen');
        }
        $usage = $payload['usage'] ?? [];
        return [
            'questions' => $questions,
            'inputtokens' => (int)($usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0),
            'outputtokens' => (int)($usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0),
        ];
    }

    /**
     * Splits source documents into provider-sized chunks.
     *
     * @param array $documents Extracted documents.
     * @param int $limit Maximum characters per chunk.
     * @return array
     */
    private function build_chunks(array $documents, int $limit): array {
        $chunks = [];
        $current = [];
        $used = 0;
        foreach ($documents as $document) {
            $remaining = $document['text'];
            while ($remaining !== '') {
                $overhead = \core_text::strlen($document['reference']) + 32;
                $available = $limit - $used - $overhead;
                if ($available < 500 && $current) {
                    $chunks[] = $current;
                    $current = [];
                    $used = 0;
                    $available = $limit - $overhead;
                }
                $piece = \core_text::substr($remaining, 0, max(1, $available));
                $remaining = \core_text::substr($remaining, \core_text::strlen($piece));
                $current[] = ['reference' => $document['reference'], 'cmid' => $document['cmid'], 'text' => $piece];
                $used += \core_text::strlen($piece) + $overhead;
                if ($used >= $limit) {
                    $chunks[] = $current;
                    $current = [];
                    $used = 0;
                }
            }
        }
        if ($current) {
            $chunks[] = $current;
        }
        return $chunks;
    }

    /**
     * Rejects malformed, ungrounded, and duplicate provider questions.
     *
     * @param array $questions Provider questions.
     * @param array $documents Complete selected source set.
     * @param int $limit Maximum accepted questions.
     * @return array Validated questions.
     */
    public function validate_questions(array $questions, array $documents, int $limit): array {
        $sources = [];
        foreach ($documents as $document) {
            $sources[$document['reference']] = $this->normalise($document['text']);
        }
        $valid = [];
        $seen = [];
        foreach ($questions as $question) {
            if (
                !is_array($question) || !is_scalar($question['question'] ?? null) ||
                    !is_scalar($question['source_reference'] ?? null) ||
                    !is_scalar($question['source_evidence'] ?? null)
            ) {
                continue;
            }
            $text = trim((string)$question['question']);
            $fingerprint = sha1(\core_text::strtolower($text));
            $reference = (string)$question['source_reference'];
            $evidence = $this->normalise((string)$question['source_evidence']);
            $options = $question['options'] ?? [];
            $validoptions = is_array($options) && count($options) === 4 &&
                count(array_filter($options, static fn($option) => is_scalar($option) && trim((string)$option) !== '')) === 4;
            $correctanswer = filter_var(
                $question['correct_answer'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 0, 'max_range' => 3]]
            );
            if (
                $text === '' || !$validoptions || $correctanswer === false ||
                    !isset($sources[$reference]) || $evidence === '' ||
                    strpos($sources[$reference], $evidence) === false || isset($seen[$fingerprint])
            ) {
                continue;
            }
            $question['question'] = $text;
            $question['options'] = array_map(static fn($option) => trim((string)$option), $options);
            $question['correct_answer'] = $correctanswer;
            $question['explanation'] = is_scalar($question['explanation'] ?? null) ? trim((string)$question['explanation']) : '';
            $question['source_reference'] = $reference;
            $seen[$fingerprint] = true;
            $valid[] = $question;
            if (count($valid) >= $limit) {
                break;
            }
        }
        return $valid;
    }

    /**
     * Normalizes text for evidence comparison.
     *
     * @param string $text Input text.
     * @return string
     */
    private function normalise(string $text): string {
        return \core_text::strtolower(trim(preg_replace('/\s+/u', ' ', $text)));
    }

    /**
     * Reads an isolated-test override or the server-side Moodle setting.
     *
     * @param string $name Setting name.
     * @return mixed
     */
    private function setting(string $name): mixed {
        return array_key_exists($name, $this->overrides)
            ? $this->overrides[$name]
            : get_config('local_aiquizgen', $name);
    }
}
