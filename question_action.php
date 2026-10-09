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
 * Approves or deletes one teacher-owned generated draft question.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$jobid = required_param('jobid', PARAM_INT);
$questionid = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$course = get_course($courseid);
$context = context_course::instance($courseid);
require_login($course);
require_capability('local/aiquizgen:generate', $context);
if (!data_submitted()) {
    throw new moodle_exception('invalidrequest');
}
require_sesskey();
$DB->get_record('local_aiquizgen_jobs', ['id' => $jobid, 'courseid' => $courseid, 'userid' => $USER->id], '*', MUST_EXIST);
$question = $DB->get_record('local_aiquizgen_questions', ['id' => $questionid, 'jobid' => $jobid], '*', MUST_EXIST);
$returnurl = new moodle_url('/local/aiquizgen/preview.php', ['courseid' => $courseid, 'jobid' => $jobid]);

if ($action === 'approve') {
    $DB->set_field('local_aiquizgen_questions', 'approved', 1, ['id' => $question->id]);
    redirect($returnurl);
}
if ($action === 'delete') {
    if (!optional_param('confirm', 0, PARAM_BOOL)) {
        $PAGE->set_url('/local/aiquizgen/question_action.php');
        $PAGE->set_context($context);
        $PAGE->set_course($course);
        $PAGE->set_title(get_string('confirmdeletequestion', 'local_aiquizgen'));
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            get_string('confirmdeletequestion', 'local_aiquizgen'),
            new single_button(new moodle_url('/local/aiquizgen/question_action.php', [
                'courseid' => $courseid, 'jobid' => $jobid, 'id' => $questionid,
                'action' => 'delete', 'confirm' => 1, 'sesskey' => sesskey(),
            ]), get_string('delete'), 'post'),
            new single_button($returnurl, get_string('cancel'), 'get')
        );
        echo $OUTPUT->footer();
        exit;
    }
    $DB->delete_records('local_aiquizgen_questions', ['id' => $question->id]);
    redirect($returnurl);
}
throw new moodle_exception('invalidparameter');
