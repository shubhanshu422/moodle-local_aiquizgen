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
 * Tests source selection and background-task failure handling.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class workflow_test extends \advanced_testcase {
    public function test_scanner_respects_selected_sections(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['numsections' => 2]);
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'section' => 1,
            'name' => 'PHP loops',
            'content' => '<p>A while loop repeats while its condition remains true.</p>',
        ]);

        $scanner = new \local_aiquizgen\local\content_scanner();
        $included = $scanner->scan($course->id, [1]);
        $excluded = $scanner->scan($course->id, [2]);

        $this->assertCount(1, $included);
        $this->assertSame((int)$page->cmid, (int)$included[0]['cmid']);
        $this->assertStringContainsString('while loop', $included[0]['text']);
        $this->assertSame([], $excluded);
    }

    public function test_adhoc_task_records_provider_connection_failure(): void {
        global $DB;

        $this->expectOutputRegex('/AI service request failed: HTTP 0/');
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('page', [
            'course' => $course->id,
            'name' => 'Course source',
            'content' => 'A loop repeats instructions.',
        ]);
        set_config('endpoint', 'http://127.0.0.1:1/unavailable', 'local_aiquizgen');
        set_config('apikey', 'test-only-invalid-key', 'local_aiquizgen');
        set_config('model', 'test-model', 'local_aiquizgen');

        $jobid = \local_aiquizgen\local\job_manager::queue($course->id, get_admin()->id, [
            'questioncount' => 1,
            'difficulty' => 'easy',
            'language' => 'English',
            'sectionids' => [],
        ]);
        $task = new \local_aiquizgen\task\generate_questions();
        $task->set_custom_data(['jobid' => $jobid]);
        $task->set_userid(get_admin()->id);
        $task->execute();

        $job = $DB->get_record('local_aiquizgen_jobs', ['id' => $jobid], '*', MUST_EXIST);
        $this->assertSame('failed', $job->status);
        $this->assertNotEmpty($job->error);
        $this->assertGreaterThan(0, $job->timecompleted);
    }
}
