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
 * Tests sequential generated quiz naming.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class quiz_namer_test extends \advanced_testcase {
    public function test_next_name_uses_highest_existing_suffix(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Physics 101']);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $generator->create_instance(['course' => $course->id, 'name' => 'Physics 101 – Practice Quiz 1']);
        $generator->create_instance(['course' => $course->id, 'name' => 'Physics 101 – Practice Quiz 4']);
        $this->assertSame(
            'Physics 101 – Practice Quiz 5',
            \local_aiquizgen\local\quiz_namer::next_name($course)
        );
    }
}
