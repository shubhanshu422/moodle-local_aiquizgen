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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for editing one generated draft question.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_aiquizgen_question_form extends moodleform {
    /**
     * Defines editable question, answer, feedback, and source fields.
     */
    public function definition(): void {
        $m = $this->_form;
        foreach (['courseid', 'jobid', 'id'] as $field) {
            $m->addElement('hidden', $field);
            $m->setType($field, PARAM_INT);
        }
        $m->addElement('textarea', 'questiontext', get_string('question'), ['rows' => 4, 'cols' => 80]);
        $m->setType('questiontext', PARAM_TEXT);
        for ($i = 0; $i < 4; $i++) {
            $m->addElement('text', 'answer' . $i, get_string('answer', 'quiz') . ' ' . ($i + 1));
            $m->setType('answer' . $i, PARAM_TEXT);
        }
        $m->addElement(
            'select',
            'correctanswer',
            get_string('correctanswer', 'question'),
            [0 => '1', 1 => '2', 2 => '3', 3 => '4']
        );
        $m->addElement('textarea', 'explanation', get_string('explanation', 'local_aiquizgen'));
        $m->setType('explanation', PARAM_TEXT);
        $m->addElement('text', 'sourceref', get_string('source', 'local_aiquizgen'));
        $m->setType('sourceref', PARAM_TEXT);
        $this->add_action_buttons();
    }
}
$courseid = required_param('courseid', PARAM_INT);
$jobid = required_param('jobid', PARAM_INT);
$id = required_param('id', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($courseid);
require_login($course);
require_capability('local/aiquizgen:generate', $context);
$job = $DB->get_record('local_aiquizgen_jobs', ['id' => $jobid, 'courseid' => $courseid, 'userid' => $USER->id], '*', MUST_EXIST);
$q = $DB->get_record('local_aiquizgen_questions', ['id' => $id, 'jobid' => $jobid], '*', MUST_EXIST);
$url = new moodle_url('/local/aiquizgen/question.php', compact('courseid', 'jobid', 'id'));
$back = new moodle_url('/local/aiquizgen/preview.php', compact('courseid', 'jobid'));
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('edit'));
$form = new local_aiquizgen_question_form();
if ($form->is_cancelled()) {
    redirect($back);
}
if ($data = $form->get_data()) {
    $q->questiontext = $data->questiontext;
    $q->answersjson = json_encode([$data->answer0, $data->answer1, $data->answer2, $data->answer3]);
    $q->correctanswer = $data->correctanswer;
    $q->explanation = $data->explanation;
    $q->sourceref = $data->sourceref;
    $q->approved = 0;
    $q->timemodified = time();
    $DB->update_record('local_aiquizgen_questions', $q);
    redirect($back);
}
$answers = json_decode($q->answersjson);
$form->set_data((object)[
    'courseid' => $courseid,
    'jobid' => $jobid,
    'id' => $id,
    'questiontext' => $q->questiontext,
    'answer0' => $answers[0],
    'answer1' => $answers[1],
    'answer2' => $answers[2],
    'answer3' => $answers[3],
    'correctanswer' => $q->correctanswer,
    'explanation' => $q->explanation,
    'sourceref' => $q->sourceref,
]);
echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
