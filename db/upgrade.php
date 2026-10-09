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
 * Applies historical database upgrades while preserving generation data.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Upgrades plugin tables from an installed version.
 *
 * @param int $oldversion Previously installed version.
 * @return bool
 */
function xmldb_local_aiquizgen_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026100900) {
        $table = new xmldb_table('local_aiquizgen_jobs');
        $fields = [
            new xmldb_field('questioncount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'error'),
            new xmldb_field('inputtokens', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'questioncount'),
            new xmldb_field('outputtokens', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'inputtokens'),
            new xmldb_field('apicalls', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'outputtokens'),
            new xmldb_field('estimatedcost', XMLDB_TYPE_NUMBER, '12, 6', null, XMLDB_NOTNULL, null, '0', 'apicalls'),
            new xmldb_field('model', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'unknown', 'estimatedcost'),
            new xmldb_field('retryof', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'model'),
            new xmldb_field('timecompleted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'retryof'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        $index = new xmldb_index('status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        upgrade_plugin_savepoint(true, 2026100900, 'local', 'aiquizgen');
    }
    if ($oldversion < 2026100901) {
        $table = new xmldb_table('local_aiquizgen_jobs');
        $field = new xmldb_field('model', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'unknown', 'estimatedcost');
        $dbman->change_field_default($table, $field);
        $fallbackmodel = (string)get_config('local_aiquizgen', 'model') ?: 'unknown';
        foreach ($DB->get_records('local_aiquizgen_jobs') as $job) {
            $changes = (object)['id' => $job->id];
            $changed = false;
            if ((int)$job->questioncount === 0) {
                $changes->questioncount = $DB->count_records('local_aiquizgen_questions', ['jobid' => $job->id]);
                $changed = $changes->questioncount > 0;
            }
            if ($job->model === '') {
                $changes->model = $fallbackmodel;
                $changed = true;
            }
            if ($changed) {
                $DB->update_record('local_aiquizgen_jobs', $changes);
            }
        }
        upgrade_plugin_savepoint(true, 2026100901, 'local', 'aiquizgen');
    }
    return true;
}
