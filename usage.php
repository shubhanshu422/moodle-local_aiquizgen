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
 * Displays teacher-owned generation usage and configured cost estimates.
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

$PAGE->set_url('/local/aiquizgen/usage.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('usage', 'local_aiquizgen'));
$PAGE->set_heading(format_string($course->fullname));
$jobs = $DB->get_records('local_aiquizgen_jobs', ['courseid' => $courseid, 'userid' => $USER->id], 'id DESC');

$totals = ['questions' => 0, 'input' => 0, 'output' => 0, 'cost' => 0.0, 'calls' => 0];
foreach ($jobs as $job) {
    $totals['questions'] += $job->questioncount;
    $totals['input'] += $job->inputtokens;
    $totals['output'] += $job->outputtokens;
    $totals['cost'] += $job->estimatedcost;
    $totals['calls'] += $job->apicalls;
}

echo $OUTPUT->header();
local_aiquizgen_output_navigation($courseid, 'usage');
echo $OUTPUT->heading(get_string('usageoverview', 'local_aiquizgen'));
$cards = [
    get_string('questions', 'local_aiquizgen') => number_format($totals['questions']),
    get_string('inputtokens', 'local_aiquizgen') => number_format($totals['input']),
    get_string('outputtokens', 'local_aiquizgen') => number_format($totals['output']),
    get_string('apicalls', 'local_aiquizgen') => number_format($totals['calls']),
    get_string('totalcost', 'local_aiquizgen') => '$' . number_format($totals['cost'], 6),
];
echo html_writer::start_div('row mb-4');
foreach ($cards as $label => $value) {
    echo html_writer::div(html_writer::div(
        html_writer::tag('div', s($label), ['class' => 'text-muted small']) .
        html_writer::tag('div', s($value), ['class' => 'h4 mb-0']),
        'card card-body shadow-sm h-100'
    ), 'col-md mb-3');
}
echo html_writer::end_div();

if ($jobs) {
    $table = new html_table();
    $table->attributes['class'] = 'table table-striped';
    $table->head = [get_string('generationid', 'local_aiquizgen'), get_string('questions', 'local_aiquizgen'),
        get_string('inputtokens', 'local_aiquizgen'), get_string('outputtokens', 'local_aiquizgen'),
        get_string('apicalls', 'local_aiquizgen'), get_string('totalcost', 'local_aiquizgen'),
        get_string('model', 'local_aiquizgen'), get_string('status', 'local_aiquizgen')];
    foreach ($jobs as $job) {
        $table->data[] = ['#' . $job->id, $job->questioncount, number_format($job->inputtokens),
            number_format($job->outputtokens), $job->apicalls, '$' . number_format($job->estimatedcost, 6),
            s($job->model ?: '—'), s(ucfirst($job->status))];
    }
    echo html_writer::table($table);
}
echo $OUTPUT->footer();
