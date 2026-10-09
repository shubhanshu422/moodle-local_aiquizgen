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

namespace local_aiquizgen;

/**
 * Tests provider-response grounding and structural validation.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class ai_client_test extends \advanced_testcase {
    /** @var array Representative selected course source. */
    private array $documents = [[
        'reference' => 'Loops page',
        'cmid' => 7,
        'text' => 'A while loop repeats a block while its condition remains true.',
    ]];

    public function test_validator_accepts_grounded_question(): void {
        $questions = (new \local_aiquizgen\local\ai_client())->validate_questions(
            [$this->valid_question()],
            $this->documents,
            1
        );

        $this->assertCount(1, $questions);
        $this->assertSame(1, $questions[0]['correct_answer']);
    }

    public function test_validator_rejects_missing_source_evidence(): void {
        $question = $this->valid_question();
        $question['source_evidence'] = 'This sentence is not in the source.';

        $questions = (new \local_aiquizgen\local\ai_client())->validate_questions(
            [$question],
            $this->documents,
            1
        );

        $this->assertSame([], $questions);
    }

    public function test_validator_rejects_non_scalar_option_and_invalid_answer_index(): void {
        $invalidoption = $this->valid_question();
        $invalidoption['options'][0] = ['unexpected' => 'array'];
        $invalidanswer = $this->valid_question();
        $invalidanswer['question'] = 'Which loop repeats conditionally?';
        $invalidanswer['correct_answer'] = 'not-an-index';

        $questions = (new \local_aiquizgen\local\ai_client())->validate_questions(
            [$invalidoption, $invalidanswer],
            $this->documents,
            2
        );

        $this->assertSame([], $questions);
    }

    public function test_validator_rejects_duplicate_question_text(): void {
        $questions = (new \local_aiquizgen\local\ai_client())->validate_questions(
            [$this->valid_question(), $this->valid_question()],
            $this->documents,
            2
        );

        $this->assertCount(1, $questions);
    }

    /**
     * Returns a structurally valid source-grounded provider question.
     *
     * @return array
     */
    private function valid_question(): array {
        return [
            'question' => 'When does a while loop repeat a block?',
            'options' => ['Forever', 'While its condition remains true', 'Once', 'Never'],
            'correct_answer' => 1,
            'explanation' => 'The condition controls repetition.',
            'source_reference' => 'Loops page',
            'source_evidence' => 'while loop repeats a block while its condition remains true',
        ];
    }
}
