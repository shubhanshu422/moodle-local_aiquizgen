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

/**
 * Regenerates one teacher-owned draft from its selected course sources.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
$courseid = required_param('courseid', PARAM_INT);
$jobid = required_param('jobid', PARAM_INT);
$id = required_param('id', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($courseid);
require_login($course);
require_capability('local/aiquizgen:generate', $context);
if (!data_submitted()) {
    throw new moodle_exception('invalidrequest');
}
require_sesskey();
$job = $DB->get_record('local_aiquizgen_jobs', ['id' => $jobid, 'courseid' => $courseid, 'userid' => $USER->id], '*', MUST_EXIST);
$draft = $DB->get_record('local_aiquizgen_questions', ['id' => $id, 'jobid' => $jobid], '*', MUST_EXIST);
$config = json_decode($job->configjson, true);
$documents = (new \local_aiquizgen\local\content_scanner())->scan($courseid, $config['sectionids']);
$result = (new \local_aiquizgen\local\ai_client())->generate($documents, 1, $config['difficulty'], $config['language']);
$questions = $result['questions'];
if ($questions) {
    $q = reset($questions);
    $draft->questiontext = $q['question'];
    $draft->answersjson = json_encode($q['options']);
    $draft->correctanswer = $q['correct_answer'];
    $draft->explanation = $q['explanation'] ?? '';
    $draft->sourceref = $q['source_reference'];
    $draft->approved = 0;
    $draft->timemodified = time();
    $DB->update_record('local_aiquizgen_questions', $draft);
    $job->inputtokens += $result['inputtokens'];
    $job->outputtokens += $result['outputtokens'];
    $job->apicalls += $result['apicalls'];
    $job->model = $result['model'];
    $job->estimatedcost = round(($job->inputtokens * (float)get_config('local_aiquizgen', 'inputcost') +
        $job->outputtokens * (float)get_config('local_aiquizgen', 'outputcost')) / 1000000, 6);
    $job->timemodified = time();
    $DB->update_record('local_aiquizgen_jobs', $job);
}
redirect(new moodle_url('/local/aiquizgen/preview.php', compact('courseid', 'jobid')));
