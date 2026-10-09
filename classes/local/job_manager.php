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
 * Creates generation jobs and queues their background tasks.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class job_manager {
    /**
     * Persists and queues a generation request.
     *
     * @param int $courseid Course identifier.
     * @param int $userid Requesting teacher identifier.
     * @param array $config Validated generation configuration.
     * @param int|null $retryof Original job identifier when retrying.
     * @return int New job identifier.
     */
    public static function queue(int $courseid, int $userid, array $config, ?int $retryof = null): int {
        global $DB;
        $now = time();
        $jobid = $DB->insert_record('local_aiquizgen_jobs', (object)[
            'courseid' => $courseid,
            'userid' => $userid,
            'status' => 'queued',
            'configjson' => json_encode($config),
            'questioncount' => 0,
            'inputtokens' => 0,
            'outputtokens' => 0,
            'apicalls' => 0,
            'estimatedcost' => 0,
            'model' => (string)get_config('local_aiquizgen', 'model') ?: 'unknown',
            'retryof' => $retryof,
            'timecompleted' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $task = new \local_aiquizgen\task\generate_questions();
        $task->set_custom_data(['jobid' => $jobid]);
        $task->set_userid($userid);
        \core\task\manager::queue_adhoc_task($task);
        return $jobid;
    }
}
