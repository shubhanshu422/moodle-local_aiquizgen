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

namespace local_aiquizgen\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Describes, exports, and deletes plugin-owned teacher generation data.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_user_data_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describes stored and externally transmitted data.
     *
     * @param collection $collection Metadata collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_aiquizgen_jobs', [
            'userid' => 'privacy:metadata:local_aiquizgen_jobs:userid',
            'configjson' => 'privacy:metadata:local_aiquizgen_jobs:configjson',
            'inputtokens' => 'privacy:metadata:local_aiquizgen_jobs:inputtokens',
            'outputtokens' => 'privacy:metadata:local_aiquizgen_jobs:outputtokens',
            'estimatedcost' => 'privacy:metadata:local_aiquizgen_jobs:estimatedcost',
            'model' => 'privacy:metadata:local_aiquizgen_jobs:model',
        ], 'privacy:metadata:local_aiquizgen_jobs');
        $collection->add_database_table('local_aiquizgen_questions', [
            'questiontext' => 'privacy:metadata:local_aiquizgen_questions:questiontext',
        ], 'privacy:metadata:local_aiquizgen_questions');
        $collection->add_external_location_link('aiprovider', [
            'coursecontent' => 'privacy:metadata:aiprovider:coursecontent',
        ], 'privacy:metadata:aiprovider');
        return $collection;
    }

    /**
     * Finds course contexts containing data owned by a user.
     *
     * @param int $userid User identifier.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {local_aiquizgen_jobs} job
                    ON job.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel AND job.userid = :userid";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_COURSE, 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * Exports a user's generation jobs and drafts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            $jobs = $DB->get_records('local_aiquizgen_jobs', [
                'courseid' => $context->instanceid,
                'userid' => $contextlist->get_user()->id,
            ], 'id ASC');
            $export = [];
            foreach ($jobs as $job) {
                $questions = $DB->get_records('local_aiquizgen_questions', ['jobid' => $job->id], 'id ASC');
                $export[] = (object)[
                    'generationid' => $job->id,
                    'status' => $job->status,
                    'configuration' => json_decode($job->configjson, true),
                    'inputtokens' => $job->inputtokens,
                    'outputtokens' => $job->outputtokens,
                    'estimatedcost' => $job->estimatedcost,
                    'model' => $job->model,
                    'created' => transform::datetime($job->timecreated),
                    'questions' => array_values(array_map(static fn($question) => (object)[
                        'question' => $question->questiontext,
                        'answers' => json_decode($question->answersjson, true),
                        'source' => $question->sourceref,
                        'approved' => transform::yesno($question->approved),
                    ], $questions)),
                ];
            }
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_aiquizgen')],
                (object)['generations' => $export]
            );
        }
    }

    /**
     * Deletes all plugin-owned jobs and drafts in a course context.
     *
     * @param \context $context Context to erase.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_COURSE) {
            return;
        }
        self::delete_jobs($DB->get_fieldset_select(
            'local_aiquizgen_jobs',
            'id',
            'courseid = :courseid',
            ['courseid' => $context->instanceid]
        ));
    }

    /**
     * Deletes one user's jobs and drafts in approved contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_COURSE) {
                continue;
            }
            self::delete_jobs($DB->get_fieldset_select(
                'local_aiquizgen_jobs',
                'id',
                'courseid = :courseid AND userid = :userid',
                ['courseid' => $context->instanceid, 'userid' => $contextlist->get_user()->id]
            ));
        }
    }

    /**
     * Deletes jobs and their dependent draft questions atomically.
     *
     * @param array $jobids Job identifiers.
     */
    private static function delete_jobs(array $jobids): void {
        global $DB;
        if (!$jobids) {
            return;
        }
        $transaction = $DB->start_delegated_transaction();
        [$insql, $params] = $DB->get_in_or_equal($jobids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_aiquizgen_questions', "jobid {$insql}", $params);
        $DB->delete_records_select('local_aiquizgen_jobs', "id {$insql}", $params);
        $transaction->allow_commit();
    }
}
