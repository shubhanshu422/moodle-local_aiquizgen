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

/**
 * Tests secure formatting of generated questions and feedback.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_form_builder_test extends \advanced_testcase {
    /**
     * Generated markup, code, Unicode, and maths remain literal plain text.
     */
    public function test_generated_content_uses_plain_text_format(): void {
        $draft = (object)[
            'questiontext' => '<script>alert(1)</script> PHP: $x < 5; π ≈ 3.14; x²',
            'answersjson' => json_encode(['<b>four</b>', 'five', 'six', 'seven']),
            'correctanswer' => 0,
            'explanation' => '<img src=x onerror=alert(1)> Correct because x² is shown.',
            'sourceref' => 'Section <Loops> — 日本語',
        ];

        $form = \local_aiquizgen\local\question_form_builder::build($draft, 42);

        $this->assertSame(FORMAT_PLAIN, $form->questiontext['format']);
        $this->assertSame($draft->questiontext, $form->questiontext['text']);
        $this->assertSame(FORMAT_PLAIN, $form->generalfeedback['format']);
        $this->assertSame(FORMAT_PLAIN, $form->correctfeedback['format']);
        $this->assertSame(FORMAT_PLAIN, $form->incorrectfeedback['format']);
        $this->assertSame('<b>four</b>', $form->answer[0]['text']);
        $this->assertSame(FORMAT_PLAIN, $form->answer[0]['format']);
        $this->assertSame([1, 0, 0, 0], $form->fraction);
    }
}
