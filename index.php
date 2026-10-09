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
 * Displays the course generator form and queues validated requests.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
$context = context_course::instance($courseid);
require_login($course);
require_capability('local/aiquizgen:generate', $context);

$PAGE->set_url('/local/aiquizgen/index.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_title(get_string('pluginname', 'local_aiquizgen'));
$PAGE->set_heading(format_string($course->fullname));

$sectionoptions = [0 => get_string('allsections', 'local_aiquizgen')];
foreach (get_fast_modinfo($course)->get_section_info_all() as $section) {
    if ($section->section > 0) {
        $sectionoptions[$section->section] = get_section_name($course, $section);
    }
}
$form = new \local_aiquizgen\generator_form(null, ['sections' => $sectionoptions]);
$form->set_data(['courseid' => $courseid]);
if ($form->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
} else if ($data = $form->get_data()) {
    $dailylimit = (int)get_config('local_aiquizgen', 'dailygenerationlimit');
    if (
        $dailylimit > 0 && $DB->count_records_select(
            'local_aiquizgen_jobs',
            'userid = :userid AND timecreated >= :daystart',
            ['userid' => $USER->id, 'daystart' => strtotime('today')]
        ) >= $dailylimit
    ) {
        throw new moodle_exception('ratelimitreached', 'local_aiquizgen');
    }
    $sections = array_values(array_filter(array_map('intval', $data->sectionids ?? [])));
    $maxquestions = max(1, (int)(get_config('local_aiquizgen', 'maxquestions') ?: 50));
    $jobid = \local_aiquizgen\local\job_manager::queue($courseid, $USER->id, [
        'questioncount' => min($maxquestions, max(1, (int)$data->questioncount)),
        'difficulty' => clean_param($data->difficulty, PARAM_ALPHA),
        'language' => clean_param($data->language, PARAM_TEXT),
        'sectionids' => $sections,
    ]);
    $previewurl = new moodle_url(
        '/local/aiquizgen/preview.php',
        ['courseid' => $courseid, 'jobid' => $jobid]
    );
    redirect($previewurl, get_string('jobqueued', 'local_aiquizgen'));
}

echo $OUTPUT->header();
local_aiquizgen_output_navigation($courseid, 'generate');
echo $OUTPUT->heading(get_string('pluginname', 'local_aiquizgen'));
$form->display();
echo $OUTPUT->footer();
