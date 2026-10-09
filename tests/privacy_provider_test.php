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

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use local_aiquizgen\privacy\provider;

/**
 * Tests export and deletion of plugin-owned personal data.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class privacy_provider_test extends \advanced_testcase {
    /**
     * Export includes the teacher's drafts but never site API credentials.
     */
    public function test_export_excludes_api_key(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        set_config('apikey', 'secret-key-must-not-export', 'local_aiquizgen');
        $jobid = $this->create_job($course->id, $user->id);
        $this->create_question($jobid);

        writer::reset();
        provider::export_user_data(new approved_contextlist($user, 'local_aiquizgen', [$context->id]));
        $data = writer::with_context($context)->get_data([get_string('pluginname', 'local_aiquizgen')]);
        $encoded = json_encode($data);

        $this->assertStringContainsString('What does a loop do?', $encoded);
        $this->assertStringNotContainsString('secret-key-must-not-export', $encoded);
        $this->assertTrue($DB->record_exists('local_aiquizgen_jobs', ['id' => $jobid]));
    }

    /**
     * User deletion removes only that user's jobs and dependent drafts.
     */
    public function test_delete_data_for_user_preserves_other_users(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $jobid = $this->create_job($course->id, $user->id);
        $otherjobid = $this->create_job($course->id, $other->id);
        $this->create_question($jobid);
        $this->create_question($otherjobid);

        provider::delete_data_for_user(new approved_contextlist($user, 'local_aiquizgen', [$context->id]));

        $this->assertFalse($DB->record_exists('local_aiquizgen_jobs', ['id' => $jobid]));
        $this->assertFalse($DB->record_exists('local_aiquizgen_questions', ['jobid' => $jobid]));
        $this->assertTrue($DB->record_exists('local_aiquizgen_jobs', ['id' => $otherjobid]));
        $this->assertTrue($DB->record_exists('local_aiquizgen_questions', ['jobid' => $otherjobid]));
    }

    /**
     * Context deletion removes every job and draft in that course only.
     */
    public function test_delete_all_users_in_context(): void {
        global $DB;
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $jobid = $this->create_job($course->id, $user->id);
        $otherjobid = $this->create_job($othercourse->id, $user->id);
        $this->create_question($jobid);
        $this->create_question($otherjobid);

        provider::delete_data_for_all_users_in_context(\context_course::instance($course->id));

        $this->assertFalse($DB->record_exists('local_aiquizgen_jobs', ['id' => $jobid]));
        $this->assertFalse($DB->record_exists('local_aiquizgen_questions', ['jobid' => $jobid]));
        $this->assertTrue($DB->record_exists('local_aiquizgen_jobs', ['id' => $otherjobid]));
    }

    /**
     * Inserts a representative generation job.
     *
     * @param int $courseid Course identifier.
     * @param int $userid User identifier.
     * @return int
     */
    private function create_job(int $courseid, int $userid): int {
        global $DB;
        $now = time();
        return $DB->insert_record('local_aiquizgen_jobs', (object)[
            'courseid' => $courseid,
            'userid' => $userid,
            'status' => 'completed',
            'configjson' => json_encode(['difficulty' => 'easy']),
            'questioncount' => 1,
            'model' => 'test-model',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Inserts a dependent draft question.
     *
     * @param int $jobid Generation job identifier.
     */
    private function create_question(int $jobid): void {
        global $DB;
        $now = time();
        $DB->insert_record('local_aiquizgen_questions', (object)[
            'jobid' => $jobid,
            'questiontext' => 'What does a loop do?',
            'answersjson' => json_encode(['Repeats', 'Stops', 'Deletes', 'Compiles']),
            'correctanswer' => 0,
            'explanation' => 'A loop repeats instructions.',
            'sourceref' => 'Page: Loops',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
