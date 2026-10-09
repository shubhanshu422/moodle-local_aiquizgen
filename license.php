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
 * Admin-only status page for explicitly enabled optional external services.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/aiquizgen:manage', $context);
if (!get_config('local_aiquizgen', 'enableoptionalservices')) {
    throw new moodle_exception('optionalservicesdisabled', 'local_aiquizgen');
}
$PAGE->set_url('/local/aiquizgen/license.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('licensestatus', 'local_aiquizgen'));
$PAGE->set_heading(get_string('pluginname', 'local_aiquizgen'));

$action = optional_param('action', '', PARAM_ALPHA);
if ($action !== '') {
    if (!data_submitted()) {
        throw new moodle_exception('invalidrequest');
    }
    require_sesskey();
    $client = new \local_aiquizgen\local\license_client();
    try {
        if ($action === 'activate') {
            $client->activate();
        } else if ($action === 'validate') {
            $client->validate();
        } else if ($action === 'deactivate') {
            $client->deactivate();
        } else {
            throw new moodle_exception('invalidparameter');
        }
        redirect($PAGE->url, get_string('licenseactioncomplete', 'local_aiquizgen'));
    } catch (Throwable $exception) {
        redirect($PAGE->url, $exception->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
}

$status = (string)get_config('local_aiquizgen', 'license_status') ?: 'invalid';
$statusclasses = [
    'active' => 'success', 'expired' => 'warning', 'suspended' => 'warning',
    'revoked' => 'danger', 'invalid' => 'secondary',
];
$statusclass = $statusclasses[$status] ?? 'secondary';
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('licensestatus', 'local_aiquizgen'));
echo $OUTPUT->notification(get_string('licensefailopen', 'local_aiquizgen'), 'notifyinfo');
$table = new html_table();
$table->attributes['class'] = 'table table-bordered';
$statusbadge = html_writer::tag(
    'span',
    s(ucfirst($status)),
    ['class' => 'badge badge-' . $statusclass]
);
$expiresat = (int)get_config('local_aiquizgen', 'license_expiresat');
$lastsuccess = (int)get_config('local_aiquizgen', 'license_lastsuccess');
$activations = (int)get_config('local_aiquizgen', 'license_activations');
$activationlimit = (int)get_config('local_aiquizgen', 'license_activationlimit');
$table->data = [
    [get_string('status', 'local_aiquizgen'), $statusbadge],
    [get_string('licenseplan', 'local_aiquizgen'), s(get_config('local_aiquizgen', 'license_plan') ?: '—')],
    [get_string('licenseexpiry', 'local_aiquizgen'), $expiresat ? userdate($expiresat) : '—'],
    [get_string('licenseactivations', 'local_aiquizgen'), $activations . ' / ' . ($activationlimit ?: '∞')],
    [get_string('licenselastsuccess', 'local_aiquizgen'), $lastsuccess ? userdate($lastsuccess) : '—'],
    [get_string('licenselasterror', 'local_aiquizgen'), s(get_config('local_aiquizgen', 'license_lasterror') ?: '—')],
];
echo html_writer::table($table);
$buttons = '';
foreach (['activate', 'validate', 'deactivate'] as $buttonaction) {
    $buttons .= $OUTPUT->single_button(
        new moodle_url($PAGE->url, ['action' => $buttonaction]),
        get_string('license' . $buttonaction, 'local_aiquizgen'),
        'post'
    );
}
echo html_writer::div($buttons, 'd-flex flex-wrap gap-2');
echo $OUTPUT->footer();
