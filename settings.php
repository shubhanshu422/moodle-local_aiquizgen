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
 * Defines AI provider, limits, support, and optional-service settings.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aiquizgen', get_string('pluginname', 'local_aiquizgen'));
    $ADMIN->add('localplugins', $settings);
    $settings->add(new admin_setting_configtext(
        'local_aiquizgen/endpoint',
        get_string('endpoint', 'local_aiquizgen'),
        '',
        'https://api.openai.com/v1/chat/completions',
        PARAM_URL
    ));
    $settings->add(new admin_setting_configpasswordunmask(
        'local_aiquizgen/apikey',
        get_string('apikey', 'local_aiquizgen'),
        '',
        ''
    ));
    $settings->add(new admin_setting_configtext(
        'local_aiquizgen/model',
        get_string('model', 'local_aiquizgen'),
        '',
        'gpt-4.1-mini',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtext(
        'local_aiquizgen/chunksize',
        get_string('chunksize', 'local_aiquizgen'),
        '',
        12000,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_aiquizgen/inputcost',
        get_string('inputcost', 'local_aiquizgen'),
        get_string('costhelp', 'local_aiquizgen'),
        '0',
        PARAM_FLOAT
    ));
    $settings->add(new admin_setting_configtext(
        'local_aiquizgen/outputcost',
        get_string('outputcost', 'local_aiquizgen'),
        get_string('costhelp', 'local_aiquizgen'),
        '0',
        PARAM_FLOAT
    ));
    $settings->add(new admin_setting_configtext(
        'local_aiquizgen/maxquestions',
        get_string('maxquestions', 'local_aiquizgen'),
        '',
        50,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_aiquizgen/dailygenerationlimit',
        get_string('dailygenerationlimit', 'local_aiquizgen'),
        get_string('zerounlimited', 'local_aiquizgen'),
        0,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configexecutable(
        'local_aiquizgen/pdftotextpath',
        get_string('pdftotextpath', 'local_aiquizgen'),
        get_string('pdftotextpath_desc', 'local_aiquizgen'),
        ''
    ));
    $settings->add(new admin_setting_heading(
        'local_aiquizgen/optionalservices',
        get_string('optionalservices', 'local_aiquizgen'),
        get_string('optionalservices_desc', 'local_aiquizgen')
    ));
    $settings->add(new admin_setting_configtext(
        'local_aiquizgen/vendorname',
        get_string('vendorname', 'local_aiquizgen'),
        '',
        '',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configtext(
        'local_aiquizgen/supportemail',
        get_string('supportemail', 'local_aiquizgen'),
        '',
        '',
        PARAM_EMAIL
    ));
    $settings->add(new admin_setting_configtext(
        'local_aiquizgen/documentationurl',
        get_string('documentationurl', 'local_aiquizgen'),
        '',
        '',
        PARAM_URL
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_aiquizgen/enableoptionalservices',
        get_string('enableoptionalservices', 'local_aiquizgen'),
        get_string('enableoptionalservices_desc', 'local_aiquizgen'),
        0
    ));
    $settings->add(new admin_setting_configtext(
        'local_aiquizgen/licenseserverurl',
        get_string('licenseserverurl', 'local_aiquizgen'),
        get_string('licenseserverurl_desc', 'local_aiquizgen'),
        '',
        PARAM_URL
    ));
    $settings->add(new admin_setting_configpasswordunmask(
        'local_aiquizgen/licensekey',
        get_string('licensekey', 'local_aiquizgen'),
        '',
        ''
    ));
    $settings->add(new admin_setting_configtextarea(
        'local_aiquizgen/licensepublickey',
        get_string('licensepublickey', 'local_aiquizgen'),
        get_string('licensepublickey_desc', 'local_aiquizgen'),
        ''
    ));
    if (get_config('local_aiquizgen', 'enableoptionalservices')) {
        $ADMIN->add('localplugins', new admin_externalpage(
            'local_aiquizgen_license',
            get_string('licensestatus', 'local_aiquizgen'),
            new moodle_url('/local/aiquizgen/license.php'),
            'local/aiquizgen:manage'
        ));
    }
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_aiquizgen_provider_test',
        get_string('providertest', 'local_aiquizgen'),
        new moodle_url('/local/aiquizgen/provider_test.php'),
        'local/aiquizgen:manage'
    ));
}
