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
 * Displays teacher-owned generated drafts and their review actions.
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
$job = $DB->get_record('local_aiquizgen_jobs', ['id' => $jobid, 'courseid' => $courseid, 'userid' => $USER->id], '*', MUST_EXIST);
$PAGE->set_url('/local/aiquizgen/preview.php', ['courseid' => $courseid, 'jobid' => $jobid]);

$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('pluginname', 'local_aiquizgen'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
local_aiquizgen_output_navigation($courseid, 'history');
echo html_writer::start_div('d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4');
echo html_writer::div(
    $OUTPUT->heading(get_string('pluginname', 'local_aiquizgen'), 2, 'mb-1') .
    html_writer::tag('span', s(ucfirst($job->status)), ['class' => 'badge badge-success ml-3']),
    'd-flex align-items-center'
);
echo html_writer::link(
    new moodle_url('/local/aiquizgen/index.php', ['courseid' => $courseid]),
    get_string('generate', 'local_aiquizgen'),
    ['class' => 'btn btn-outline-primary']
);
echo html_writer::end_div();

if ($job->error) {
    echo $OUTPUT->notification(s($job->error), 'notifyproblem');
}
$questions = $DB->get_records('local_aiquizgen_questions', ['jobid' => $jobid]);
if (!$questions) {
    echo $OUTPUT->notification(get_string('noquestions', 'local_aiquizgen'), 'notifyinfo');
} else {
    echo html_writer::tag('p', count($questions) . ' generated questions', ['class' => 'text-muted mb-4']);
    $number = 1;
    foreach ($questions as $question) {
        $answers = json_decode($question->answersjson, true);
        $statusbadge = $question->approved
            ? html_writer::tag('span', get_string('approved', 'local_aiquizgen'), ['class' => 'badge badge-success'])
            : html_writer::tag('span', get_string('draft', 'local_aiquizgen'), ['class' => 'badge badge-secondary']);
        $body = html_writer::start_div('d-flex justify-content-between align-items-start mb-3');
        $questionheading = $number . '. ' . format_text($question->questiontext, FORMAT_PLAIN);
        $body .= html_writer::tag('h4', $questionheading, ['class' => 'h5 mb-0 mr-3']);
        $body .= $statusbadge . html_writer::end_div();
        $body .= html_writer::start_tag('ul', ['class' => 'list-group mb-3']);
        foreach ($answers as $index => $answer) {
            $iscorrect = $index === (int)$question->correctanswer;
            $label = html_writer::tag('span', chr(65 + $index), ['class' => 'badge badge-light mr-2']);
            if ($iscorrect) {
                $label .= html_writer::tag('span', '✓', [
                    'class' => 'text-success font-weight-bold mr-2',
                    'aria-label' => get_string('correct'),
                ]);
            }
            $answerclass = 'list-group-item py-2' . ($iscorrect ? ' border-success bg-light' : '');
            $body .= html_writer::tag('li', $label . s($answer), ['class' => $answerclass]);
        }
        $body .= html_writer::end_tag('ul');
        $body .= html_writer::div(html_writer::tag('strong', get_string('explanation', 'local_aiquizgen') . ': ') .
            s($question->explanation), 'alert alert-light border mb-2');
        $body .= html_writer::tag('p', html_writer::tag('strong', get_string('source', 'local_aiquizgen') . ': ') .
            s($question->sourceref), ['class' => 'small text-muted mb-3']);

        $actions = [];
        if (!$question->approved) {
            $actions[] = $OUTPUT->single_button(new moodle_url('/local/aiquizgen/question_action.php', [
                'courseid' => $courseid, 'jobid' => $jobid, 'id' => $question->id, 'action' => 'approve',
            ]), get_string('approve', 'local_aiquizgen'), 'post', ['class' => 'btn-success']);
        }
        $actions[] = html_writer::link(
            new moodle_url(
                '/local/aiquizgen/question.php',
                ['courseid' => $courseid, 'jobid' => $jobid, 'id' => $question->id]
            ),
            get_string('edit'),
            ['class' => 'btn btn-outline-secondary']
        );
        $actions[] = $OUTPUT->single_button(
            new moodle_url(
                '/local/aiquizgen/regenerate.php',
                ['courseid' => $courseid, 'jobid' => $jobid, 'id' => $question->id]
            ),
            get_string('regenerate', 'local_aiquizgen'),
            'post',
            ['class' => 'btn-outline-primary']
        );
        $actions[] = $OUTPUT->single_button(new moodle_url('/local/aiquizgen/question_action.php', [
            'courseid' => $courseid, 'jobid' => $jobid, 'id' => $question->id, 'action' => 'delete',
        ]), get_string('delete'), 'post', ['class' => 'btn-outline-danger']);
        $body .= html_writer::div(implode('', $actions), 'd-flex flex-wrap align-items-center gap-2');
        echo html_writer::div($body, 'card card-body shadow-sm mb-4');
        $number++;
    }
    echo html_writer::start_div('d-flex justify-content-end mt-4');
    echo html_writer::link(
        new moodle_url(
            '/local/aiquizgen/publish.php',
            ['courseid' => $courseid, 'jobid' => $jobid]
        ),
        get_string('createquiz', 'local_aiquizgen'),
        ['class' => 'btn btn-primary btn-lg']
    );
    echo html_writer::end_div();
}
echo $OUTPUT->footer();
