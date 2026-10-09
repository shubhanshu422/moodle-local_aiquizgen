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
 * Handles retry and deletion actions for teacher-owned generation jobs.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$jobid = required_param('jobid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$course = get_course($courseid);
$context = context_course::instance($courseid);
require_login($course);
require_capability('local/aiquizgen:generate', $context);
if (!data_submitted()) {
    throw new moodle_exception('invalidrequest');
}
require_sesskey();
$job = $DB->get_record('local_aiquizgen_jobs', ['id' => $jobid, 'courseid' => $courseid, 'userid' => $USER->id], '*', MUST_EXIST);
$returnurl = new moodle_url('/local/aiquizgen/history.php', ['courseid' => $courseid]);

if ($action === 'retry') {
    if (!in_array($job->status, ['complete', 'failed'], true)) {
        throw new moodle_exception('invalidparameter');
    }
    $dailylimit = (int)get_config('local_aiquizgen', 'dailygenerationlimit');
    if (
        $dailylimit > 0 && $DB->count_records_select(
            'local_aiquizgen_jobs',
            'userid = :userid AND timecreated >= :daystart',
            ['userid' => $USER->id, 'daystart' => strtotime('today')]
        ) >= $dailylimit
    ) {
        throw new moodle_exception('ratelimitreached', 'local_aiquizgen');
    }
    $newid = \local_aiquizgen\local\job_manager::queue($courseid, $USER->id, json_decode($job->configjson, true), $job->id);
        $previewurl = new moodle_url(
            '/local/aiquizgen/preview.php',
            ['courseid' => $courseid, 'jobid' => $newid]
        );
        redirect($previewurl, get_string('retriedjob', 'local_aiquizgen'));
}

if ($action === 'delete') {
    if (in_array($job->status, ['queued', 'processing'], true)) {
        throw new moodle_exception('activejobcannotdelete', 'local_aiquizgen');
    }
    $transaction = $DB->start_delegated_transaction();
    $DB->delete_records('local_aiquizgen_questions', ['jobid' => $job->id]);
    $DB->delete_records('local_aiquizgen_jobs', ['id' => $job->id]);
    $transaction->allow_commit();
    redirect($returnurl, get_string('deletedjob', 'local_aiquizgen'));
}
throw new moodle_exception('invalidparameter');
