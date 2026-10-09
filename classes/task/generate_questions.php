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

namespace local_aiquizgen\task;

/**
 * Background task that scans course sources and persists generated drafts.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_questions extends \core\task\adhoc_task {
    /**
     * Executes the queued generation job.
     */
    public function execute(): void {
        global $DB;
        $data = $this->get_custom_data();
        $job = $DB->get_record('local_aiquizgen_jobs', ['id' => $data->jobid], '*', MUST_EXIST);
        $config = json_decode($job->configjson, true);
        $job->status = 'processing';
        $job->timemodified = time();
        $DB->update_record('local_aiquizgen_jobs', $job);
        try {
            $documents = (new \local_aiquizgen\local\content_scanner())->scan($job->courseid, $config['sectionids']);
            $result = (new \local_aiquizgen\local\ai_client())->generate(
                $documents,
                $config['questioncount'],
                $config['difficulty'],
                $config['language']
            );
            $questions = $result['questions'];
            foreach ($questions as $q) {
                $DB->insert_record('local_aiquizgen_questions', (object)[
                    'jobid' => $job->id, 'questiontext' => $q['question'], 'answersjson' => json_encode($q['options']),
                    'correctanswer' => $q['correct_answer'], 'explanation' => $q['explanation'] ?? '',
                    'sourceref' => $q['source_reference'], 'approved' => 0, 'timecreated' => time(), 'timemodified' => time(),
                ]);
            }
            $job->status = 'complete';
            $job->questioncount = count($questions);
            $job->inputtokens = $result['inputtokens'];
            $job->outputtokens = $result['outputtokens'];
            $job->apicalls = $result['apicalls'];
            $job->model = $result['model'];
            $inputcost = (float)get_config('local_aiquizgen', 'inputcost');
            $outputcost = (float)get_config('local_aiquizgen', 'outputcost');
            $job->estimatedcost = round(($job->inputtokens * $inputcost + $job->outputtokens * $outputcost) / 1000000, 6);
        } catch (\Throwable $e) {
            $job->status = 'failed';
            $job->error = $e->getMessage();
            mtrace($e->getMessage());
        }
        $job->timecompleted = time();
        $job->timemodified = time();
        $DB->update_record('local_aiquizgen_jobs', $job);
    }
}
