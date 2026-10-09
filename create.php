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
 * Publishes approved drafts into Moodle's Question Bank and Quiz activity.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/lib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/course/modlib.php');

$courseid = required_param('courseid', PARAM_INT);
$jobid = required_param('jobid', PARAM_INT);
$requestedquizname = trim(required_param('quizname', PARAM_TEXT));
$course = get_course($courseid);
$context = context_course::instance($courseid);
require_login($course);
require_capability('local/aiquizgen:generate', $context);
require_sesskey();
$job = $DB->get_record('local_aiquizgen_jobs', ['id' => $jobid, 'courseid' => $courseid, 'userid' => $USER->id], '*', MUST_EXIST);
$drafts = $DB->get_records('local_aiquizgen_questions', ['jobid' => $jobid, 'approved' => 1]);
if (!$drafts) {
    throw new moodle_exception('noquestions', 'local_aiquizgen');
}
if ($requestedquizname === '') {
    throw new moodle_exception('invalidparameter');
}

$lockfactory = \core\lock\lock_config::get_lock_factory('local_aiquizgen_quiz_creation');
$lock = $lockfactory->get_lock('course-' . $courseid, 10);
if (!$lock) {
    throw new moodle_exception('locktimeout', 'local_aiquizgen');
}
try {
    $transaction = $DB->start_delegated_transaction();
    $quizname = \core_text::substr($requestedquizname, 0, 255);
    if (
        $DB->record_exists('quiz', ['course' => $courseid, 'name' => $quizname]) &&
        str_starts_with($quizname, \local_aiquizgen\local\quiz_namer::prefix($course))
    ) {
        $quizname = \local_aiquizgen\local\quiz_namer::next_name($course);
    }
    $moduleinfo = (object)[
        'course' => $courseid,
        'modulename' => 'quiz',
        'module' => $DB->get_field('modules', 'id', ['name' => 'quiz'], MUST_EXIST),
        'cmidnumber' => '',
        'section' => 0,
        'visible' => 1,
        'name' => $quizname,
        'introeditor' => ['text' => '', 'format' => FORMAT_HTML, 'itemid' => 0],
        'timeopen' => 0,
        'timeclose' => 0,
        'timelimit' => 0,
        'overduehandling' => 'autosubmit',
        'graceperiod' => 0,
        'preferredbehaviour' => 'deferredfeedback',
        'attempts' => 0,
        'attemptonlast' => 0,
        'grademethod' => QUIZ_GRADEHIGHEST,
        'decimalpoints' => 2,
        'questiondecimalpoints' => -1,
        'reviewattempt' => 69904,
        'reviewcorrectness' => 4368,
        'reviewmarks' => 4368,
        'reviewspecificfeedback' => 4368,
        'reviewgeneralfeedback' => 4368,
        'reviewrightanswer' => 4368,
        'reviewoverallfeedback' => 4368,
        'questionsperpage' => 1,
        'navmethod' => 'free',
        'shuffleanswers' => 1,
        'grade' => 10,
        'quizpassword' => '',
        'subnet' => '',
        'browsersecurity' => '-',
    ];
    $moduleinfo = add_moduleinfo($moduleinfo, $course);
    $cmid = $moduleinfo->coursemodule;
    $quiz = $DB->get_record('quiz', ['id' => $moduleinfo->instance], '*', MUST_EXIST);
    $coursebankname = clean_param(strip_tags(format_string($course->fullname)), PARAM_TEXT);
    $bankname = shorten_text($coursebankname, 180, true) . ' – AI Generated Question Bank';
    $qbank = $DB->get_record('qbank', ['course' => $courseid, 'name' => $bankname]);
    if ($qbank) {
        $bankcm = get_coursemodule_from_instance('qbank', $qbank->id, $courseid, false, MUST_EXIST);
    } else {
        $bankcm = \core_question\local\bank\question_bank_helper::create_default_open_instance(
            $course,
            $bankname,
            \core_question\local\bank\question_bank_helper::TYPE_STANDARD
        );
    }
    $bankcontext = context_module::instance($bankcm->id);
    $category = question_get_default_category($bankcontext->id, true);
    if (!$category) {
        throw new coding_exception('Could not create the default category for the AI-generated question bank.');
    }

    foreach ($drafts as $draft) {
        $form = \local_aiquizgen\local\question_form_builder::build($draft, $category->id);
        $question = question_bank::get_qtype('multichoice')->save_question(
            (object)['id' => 0, 'qtype' => 'multichoice', 'courseid' => $courseid],
            $form
        );
        quiz_add_quiz_question($question->id, $quiz);
    }
    \mod_quiz\quiz_settings::create($quiz->id)->get_grade_calculator()->recompute_quiz_sumgrades();
    $transaction->allow_commit();
    rebuild_course_cache($courseid, true);
} finally {
    $lock->release();
}
redirect(new moodle_url('/mod/quiz/view.php', ['id' => $cmid]));
