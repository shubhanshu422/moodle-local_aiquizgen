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
 * Displays generation history owned by the current teacher in a course.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($courseid);
require_login($course);
require_capability('local/aiquizgen:generate', $context);

$PAGE->set_url('/local/aiquizgen/history.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('history', 'local_aiquizgen'));
$PAGE->set_heading(format_string($course->fullname));

$jobs = $DB->get_records('local_aiquizgen_jobs', ['courseid' => $courseid, 'userid' => $USER->id], 'id DESC');
echo $OUTPUT->header();
local_aiquizgen_output_navigation($courseid, 'history');
echo $OUTPUT->heading(get_string('history', 'local_aiquizgen'));

if (!$jobs) {
    echo $OUTPUT->notification(get_string('historyempty', 'local_aiquizgen'), 'notifyinfo');
} else {
    $table = new html_table();
    $table->attributes['class'] = 'table table-hover align-middle';
    $table->head = [get_string('generationid', 'local_aiquizgen'), get_string('questions', 'local_aiquizgen'),
        get_string('model', 'local_aiquizgen'), get_string('status', 'local_aiquizgen'),
        get_string('created', 'local_aiquizgen'), get_string('actions', 'local_aiquizgen')];
    foreach ($jobs as $job) {
        $config = json_decode($job->configjson, true);
        $requested = (int)($config['questioncount'] ?? 0);
        $statusclasses = [
            'complete' => 'success', 'failed' => 'danger',
            'processing' => 'primary', 'queued' => 'secondary',
        ];
        $statusclass = $statusclasses[$job->status] ?? 'secondary';
        $actions = [];
        if ($job->status === 'complete') {
            $actions[] = html_writer::link(new moodle_url(
                '/local/aiquizgen/preview.php',
                ['courseid' => $courseid, 'jobid' => $job->id]
            ), get_string('view', 'local_aiquizgen'), ['class' => 'btn btn-sm btn-outline-primary']);
        }
        if (in_array($job->status, ['complete', 'failed'], true)) {
            $actions[] = $OUTPUT->single_button(new moodle_url(
                '/local/aiquizgen/action.php',
                ['courseid' => $courseid, 'jobid' => $job->id, 'action' => 'retry']
            ), get_string('retry', 'local_aiquizgen'), 'post', ['class' => 'btn-sm']);
            $actions[] = $OUTPUT->single_button(new moodle_url(
                '/local/aiquizgen/action.php',
                ['courseid' => $courseid, 'jobid' => $job->id, 'action' => 'delete']
            ), get_string('delete'), 'post', ['class' => 'btn-sm btn-outline-danger']);
        }
        $table->data[] = [
            '#' . $job->id,
            $job->questioncount . ' / ' . $requested,
            s($job->model ?: '—'),
            html_writer::tag('span', s(ucfirst($job->status)), ['class' => 'badge badge-' . $statusclass]),
            userdate($job->timecreated),
            html_writer::div(implode('', $actions), 'd-flex flex-wrap gap-2'),
        ];
    }
    echo html_writer::table($table);
}
echo $OUTPUT->footer();
