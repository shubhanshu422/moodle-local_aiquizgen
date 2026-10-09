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

namespace local_aiquizgen\task;

/**
 * Validates optional external services only when explicitly enabled.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validate_license extends \core\task\scheduled_task {
    /**
     * Returns the localized task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('taskvalidatelicense', 'local_aiquizgen');
    }

    /**
     * Executes optional validation or exits without network access.
     */
    public function execute(): void {
        if (
            !get_config('local_aiquizgen', 'enableoptionalservices') ||
                !get_config('local_aiquizgen', 'licensekey') || !get_config('local_aiquizgen', 'licenseserverurl')
        ) {
            return;
        }
        try {
            (new \local_aiquizgen\local\license_client())->validate();
        } catch (\Throwable $exception) {
            mtrace('AI Quiz Generator license validation unavailable: ' . $exception->getMessage());
        }
    }
}
