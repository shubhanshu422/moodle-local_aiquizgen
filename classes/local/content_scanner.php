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
 * Extracts clean, teacher-visible source text from supported course activities.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_scanner {
    /**
     * Scans supported activities in the selected course sections.
     *
     * @param int $courseid Course identifier.
     * @param array $sectionids Optional section numbers to include.
     * @return array Extracted source documents.
     */
    public function scan(int $courseid, array $sectionids = []): array {
        global $DB;
        $modinfo = get_fast_modinfo($courseid);
        $documents = [];
        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible || ($sectionids && !in_array($cm->sectionnum, $sectionids))) {
                continue;
            }
            $text = '';
            switch ($cm->modname) {
                case 'page':
                    $text = (string)$DB->get_field('page', 'content', ['id' => $cm->instance]);
                    break;
                case 'label':
                    $text = (string)$DB->get_field('label', 'intro', ['id' => $cm->instance]);
                    break;
                case 'assign':
                    $text = (string)$DB->get_field('assign', 'intro', ['id' => $cm->instance]);
                    break;
                case 'lesson':
                    $pages = $DB->get_records('lesson_pages', ['lessonid' => $cm->instance], 'id', 'id,title,contents');
                    $text = implode("\n", array_map(fn($p) => $p->title . "\n" . $p->contents, $pages));
                    break;
                case 'book':
                    $chapters = $DB->get_records(
                        'book_chapters',
                        ['bookid' => $cm->instance, 'hidden' => 0],
                        'pagenum',
                        'id,title,content'
                    );
                    foreach ($chapters as $chapter) {
                        $chaptertext = $this->clean($chapter->content);
                        if ($chaptertext !== '') {
                            $documents[] = [
                                'reference' => $cm->get_formatted_name() . ' — ' . format_string($chapter->title),
                                'cmid' => $cm->id,
                                'text' => $chaptertext,
                            ];
                        }
                    }
                    continue 2;
                case 'resource':
                    $text = $this->extract_resource($cm);
                    break;
            }
            $text = $this->clean($text);
            if ($text !== '') {
                $documents[] = ['reference' => $cm->get_formatted_name(), 'cmid' => $cm->id, 'text' => $text];
            }
        }
        return $documents;
    }

    /**
     * Extracts text from a file resource.
     *
     * @param \cm_info $cm Resource course-module information.
     * @return string
     */
    private function extract_resource(\cm_info $cm): string {
        $fs = get_file_storage();
        $context = \context_module::instance($cm->id);
        foreach ($fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'filename', false) as $file) {
            $mime = $file->get_mimetype();
            if (str_starts_with($mime, 'text/')) {
                return $file->get_content();
            }
            if ($mime === 'application/pdf') {
                return $this->extract_pdf($file);
            }
        }
        return '';
    }

    /**
     * Extracts PDF text with the configured pdftotext executable.
     *
     * @param \stored_file $file PDF file.
     * @return string
     */
    private function extract_pdf(\stored_file $file): string {
        $temp = make_request_directory() . '/' . clean_param($file->get_filename(), PARAM_FILE);
        $file->copy_content_to($temp);
        $binary = get_config('local_aiquizgen', 'pdftotextpath') ?: 'pdftotext';
        $command = escapeshellcmd($binary) . ' -enc UTF-8 ' . escapeshellarg($temp) . ' -';
        exec($command, $output, $code);
        return $code === 0 ? implode("\n", $output) : '';
    }

    /**
     * Converts activity HTML into normalized plain text.
     *
     * @param string $html Source HTML.
     * @return string
     */
    private function clean(string $html): string {
        return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
}
