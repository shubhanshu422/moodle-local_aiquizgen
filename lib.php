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
 * Navigation callbacks and shared course-page navigation rendering.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Adds the generator link for users with the course capability.
 *
 * @param navigation_node $navigation Course navigation node.
 * @param stdClass $course Course record.
 * @param context_course $context Course context.
 */
function local_aiquizgen_extend_navigation_course($navigation, $course, $context): void {
    if (has_capability('local/aiquizgen:generate', $context)) {
        $url = new moodle_url('/local/aiquizgen/index.php', ['courseid' => $course->id]);
        $navigation->add(get_string('aiquizgenerator', 'local_aiquizgen'), $url, navigation_node::TYPE_CUSTOM, null, 'aiquizgen');
    }
}

/**
 * Renders navigation shared by generator, history, and usage pages.
 *
 * @param int $courseid Course identifier.
 * @param string $active Active tab key.
 */
function local_aiquizgen_output_navigation(int $courseid, string $active): void {
    $items = [
        'generate' => ['/local/aiquizgen/index.php', get_string('generate', 'local_aiquizgen')],
        'history' => ['/local/aiquizgen/history.php', get_string('history', 'local_aiquizgen')],
        'usage' => ['/local/aiquizgen/usage.php', get_string('usage', 'local_aiquizgen')],
    ];
    $links = '';
    foreach ($items as $key => [$path, $label]) {
        $class = 'nav-link' . ($key === $active ? ' active' : '');
        $links .= html_writer::tag('li', html_writer::link(
            new moodle_url($path, ['courseid' => $courseid]),
            $label,
            ['class' => $class, 'aria-current' => $key === $active ? 'page' : null]
        ), ['class' => 'nav-item']);
    }
    echo html_writer::tag('ul', $links, ['class' => 'nav nav-tabs mb-4']);
}
