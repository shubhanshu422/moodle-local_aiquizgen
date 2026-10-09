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
 * Displays the quiz publication confirmation form.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$jobid = required_param('jobid', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($courseid);
require_login($course);
require_capability('local/aiquizgen:generate', $context);
$DB->get_record('local_aiquizgen_jobs', ['id' => $jobid, 'courseid' => $courseid, 'userid' => $USER->id], '*', MUST_EXIST);
$PAGE->set_url('/local/aiquizgen/publish.php', ['courseid' => $courseid, 'jobid' => $jobid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('publishquiz', 'local_aiquizgen'));
$PAGE->set_heading(format_string($course->fullname));
$form = new \local_aiquizgen\publish_form(new moodle_url('/local/aiquizgen/create.php'), [
    'courseid' => $courseid,
    'jobid' => $jobid,
    'quizname' => \local_aiquizgen\local\quiz_namer::next_name($course),
]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/aiquizgen/preview.php', ['courseid' => $courseid, 'jobid' => $jobid]));
}
echo $OUTPUT->header();
local_aiquizgen_output_navigation($courseid, 'history');
echo $OUTPUT->heading(get_string('publishquiz', 'local_aiquizgen'));
$form->display();
echo $OUTPUT->footer();
