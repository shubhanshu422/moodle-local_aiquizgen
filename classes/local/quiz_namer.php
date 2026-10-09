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
 * Generates sequential, course-specific names for published practice quizzes.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_namer {
    /**
     * Returns the stable quiz-name prefix for a course.
     *
     * @param \stdClass $course Moodle course record.
     * @return string
     */
    public static function prefix(\stdClass $course): string {
        return clean_param(strip_tags(format_string($course->fullname)), PARAM_TEXT) . ' – Practice Quiz ';
    }

    /**
     * Returns the next unused practice-quiz name for a course.
     *
     * @param \stdClass $course Moodle course record.
     * @return string
     */
    public static function next_name(\stdClass $course): string {
        global $DB;
        $prefix = self::prefix($course);
        $like = $DB->sql_like('name', ':namepattern', false);
        $names = $DB->get_fieldset_select('quiz', 'name', "course = :courseid AND {$like}", [
            'courseid' => $course->id,
            'namepattern' => $DB->sql_like_escape($prefix) . '%',
        ]);
        $next = 1;
        foreach ($names as $name) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/u', $name, $matches)) {
                $next = max($next, (int)$matches[1] + 1);
            }
        }
        return $prefix . $next;
    }
}
