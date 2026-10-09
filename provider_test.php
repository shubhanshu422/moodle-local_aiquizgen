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
 * Admin-only AI-provider connection test page.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/aiquizgen:manage', $context);
$PAGE->set_url('/local/aiquizgen/provider_test.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('providertest', 'local_aiquizgen'));
$PAGE->set_heading(get_string('pluginname', 'local_aiquizgen'));
$message = '';
$messagetype = 'notifyinfo';
if (data_submitted()) {
    require_sesskey();
    try {
        $result = (new \local_aiquizgen\local\ai_client())->test_connection();
        $message = get_string('providertestsuccess', 'local_aiquizgen', (object)[
            'input' => $result['inputtokens'],
            'output' => $result['outputtokens'],
        ]);
        $messagetype = 'notifysuccess';
    } catch (Throwable $exception) {
        $message = $exception->getMessage();
        $messagetype = 'notifyproblem';
    }
}
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('providertest', 'local_aiquizgen'));
echo $OUTPUT->notification(get_string('providertestcostwarning', 'local_aiquizgen'), 'notifyinfo');
if ($message !== '') {
    echo $OUTPUT->notification(s($message), $messagetype);
}
echo $OUTPUT->single_button($PAGE->url, get_string('providertestbutton', 'local_aiquizgen'), 'post');
echo $OUTPUT->footer();
