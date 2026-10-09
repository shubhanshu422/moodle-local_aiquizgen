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

namespace local_aiquizgen\local;

/**
 * Builds secure Moodle multichoice forms from generated drafts.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_form_builder {
    /**
     * Builds a question form while preserving generated content as plain text.
     *
     * @param \stdClass $draft Stored generated question draft.
     * @param int $categoryid Question category identifier.
     * @return \stdClass
     */
    public static function build(\stdClass $draft, int $categoryid): \stdClass {
        $answers = json_decode($draft->answersjson, true, 512, JSON_THROW_ON_ERROR);
        $form = (object)[
            'category' => $categoryid,
            'qtype' => 'multichoice',
            'name' => shorten_text($draft->questiontext, 100),
            'questiontext' => ['text' => $draft->questiontext, 'format' => FORMAT_PLAIN],
            'generalfeedback' => [
                'text' => $draft->explanation . "\nSource: " . $draft->sourceref,
                'format' => FORMAT_PLAIN,
            ],
            'defaultmark' => 1,
            'penalty' => 0.3333333,
            'single' => 1,
            'shuffleanswers' => 1,
            'answernumbering' => 'abc',
            'showstandardinstruction' => 0,
            'shownumcorrect' => 1,
            'correctfeedback' => ['text' => $draft->explanation, 'format' => FORMAT_PLAIN],
            'partiallycorrectfeedback' => ['text' => '', 'format' => FORMAT_PLAIN],
            'incorrectfeedback' => ['text' => $draft->explanation, 'format' => FORMAT_PLAIN],
            'answer' => [],
            'fraction' => [],
            'feedback' => [],
        ];
        foreach ($answers as $i => $answer) {
            $form->answer[] = ['text' => $answer, 'format' => FORMAT_PLAIN];
            $form->fraction[] = (int)$i === (int)$draft->correctanswer ? 1 : 0;
            $form->feedback[] = ['text' => '', 'format' => FORMAT_PLAIN];
        }
        return $form;
    }
}
