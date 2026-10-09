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

namespace local_aiquizgen;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

/**
 * Form for confirming the name of a generated Moodle quiz.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class publish_form extends \moodleform {
    /**
     * Defines quiz publication fields.
     */
    public function definition(): void {
        $form = $this->_form;
        foreach (['courseid', 'jobid'] as $field) {
            $form->addElement('hidden', $field, $this->_customdata[$field]);
            $form->setType($field, PARAM_INT);
        }
        $form->addElement('text', 'quizname', get_string('quizname', 'local_aiquizgen'), ['size' => 64]);
        $form->setType('quizname', PARAM_TEXT);
        $form->setDefault('quizname', $this->_customdata['quizname']);
        $form->addRule('quizname', get_string('required'), 'required');
        $form->addRule('quizname', get_string('maximumchars', '', 255), 'maxlength', 255);
        $this->add_action_buttons(true, get_string('createquiz', 'local_aiquizgen'));
    }
}
