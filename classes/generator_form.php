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
 * Teacher form for configuring a generation job.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator_form extends \moodleform {
    /**
     * Defines the generation configuration fields.
     */
    public function definition(): void {
        $mform = $this->_form;
        $sections = $this->_customdata['sections'];
        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->addElement('text', 'questioncount', get_string('numberofquestions', 'local_aiquizgen'));
        $mform->setType('questioncount', PARAM_INT);
        $mform->setDefault('questioncount', 10);
        $mform->addRule('questioncount', null, 'required');
        $mform->addRule('questioncount', null, 'numeric');
        $mform->addElement(
            'select',
            'questiontype',
            get_string('questiontype', 'local_aiquizgen'),
            ['mcq' => get_string('mcq', 'local_aiquizgen')]
        );
        $mform->addElement('select', 'difficulty', get_string('difficulty', 'local_aiquizgen'), [
            'easy' => get_string('easy', 'local_aiquizgen'), 'medium' => get_string('medium', 'local_aiquizgen'),
            'hard' => get_string('hard', 'local_aiquizgen'), 'mixed' => get_string('mixed', 'local_aiquizgen'),
        ]);
        $mform->addElement('text', 'language', get_string('language', 'local_aiquizgen'));
        $mform->setType('language', PARAM_TEXT);
        $mform->setDefault('language', 'English');
        $mform->addElement(
            'autocomplete',
            'sectionids',
            get_string('sections', 'local_aiquizgen'),
            $sections,
            ['multiple' => true]
        );
        $this->add_action_buttons(true, get_string('generate', 'local_aiquizgen'));
    }
}
