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
 * English language strings for the AI Quiz Generator.
 *
 * @package   local_aiquizgen
 * @copyright 2026 AI Quiz Generator contributors
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['actions'] = 'Actions';
$string['activejobcannotdelete'] = 'A queued or processing generation cannot be deleted.';
$string['aiapierror'] = 'The AI service request failed: {$a}';
$string['aiquizgen:generate'] = 'Generate AI quizzes in a course';
$string['aiquizgen:manage'] = 'Manage AI quiz generator settings';
$string['aiquizgenerator'] = 'AI Quiz Generator';
$string['allsections'] = 'Entire course';
$string['apicalls'] = 'API calls';
$string['apikey'] = 'AI API key';
$string['approve'] = 'Approve';
$string['approved'] = 'Approved';
$string['chunksize'] = 'Maximum source characters per AI request';
$string['confirmdeletequestion'] = 'Delete this generated question? This cannot be undone.';
$string['costhelp'] = 'Configure the current provider/model rate. Use 0 when cost should not be estimated.';
$string['created'] = 'Created';
$string['createquiz'] = 'Create Moodle quiz from approved questions';
$string['dailygenerationlimit'] = 'Maximum generations per teacher per day';
$string['deletedjob'] = 'Generation deleted.';
$string['difficulty'] = 'Difficulty';
$string['documentationurl'] = 'Documentation URL';
$string['draft'] = 'Draft';
$string['easy'] = 'Easy';
$string['enableoptionalservices'] = 'Enable optional external service integration';
$string['enableoptionalservices_desc'] = 'Disabled by default. Enabling permits the configured service to receive the site information documented below. It is not needed for AI quiz generation.';
$string['endpoint'] = 'AI chat-completions endpoint';
$string['explanation'] = 'Explanation';
$string['generate'] = 'Generate questions';
$string['generationid'] = 'Generation ID';
$string['hard'] = 'Hard';
$string['history'] = 'Generation history';
$string['historyempty'] = 'No generations have been created for this course.';
$string['inputcost'] = 'Input cost per million tokens (USD)';
$string['inputtokens'] = 'Input tokens';
$string['insufficientcontent'] = 'The selected course content is insufficient for question generation.';
$string['invalidairesponse'] = 'The AI service returned an invalid response.';
$string['jobqueued'] = 'Generation has been queued. Refresh this page shortly.';
$string['language'] = 'Language';
$string['licenseactioncomplete'] = 'License action completed.';
$string['licenseactivate'] = 'Activate';
$string['licenseactivations'] = 'Site activations';
$string['licenseconfigurationmissing'] = 'Configure the license server URL, license key, and public verification key first.';
$string['licensedeactivate'] = 'Deactivate this site';
$string['licenseexpiry'] = 'Expiry';
$string['licensefailopen'] = 'This optional status applies only to separately provided services. It never restricts the free plugin or its core quiz-generation features.';
$string['licensehttpsrequired'] = 'The license server must use HTTPS (except localhost development).';
$string['licenseinvalidsignature'] = 'The license server response signature is invalid.';
$string['licensekey'] = 'Service license key';
$string['licenselasterror'] = 'Last validation error';
$string['licenselastsuccess'] = 'Last successful validation';
$string['licenseplan'] = 'Plan';
$string['licensepublickey'] = 'License server public key';
$string['licensepublickey_desc'] = 'PEM-encoded RSA/ECDSA public key used to verify signed license-server responses.';
$string['licenseserverunavailable'] = 'The license server is temporarily unavailable. The previous status has been retained.';
$string['licenseserverurl'] = 'License server URL';
$string['licenseserverurl_desc'] = 'Optional HTTPS service URL. Leave blank for the free public beta.';
$string['licensestatus'] = 'Service license status';
$string['licensevalidate'] = 'Revalidate';
$string['locktimeout'] = 'Another quiz is currently being created for this course. Please try again.';
$string['maxquestions'] = 'Maximum questions per generation';
$string['mcq'] = 'Multiple choice';
$string['medium'] = 'Medium';
$string['missingapikey'] = 'The AI API key has not been configured.';
$string['mixed'] = 'Mixed';
$string['model'] = 'AI model';
$string['noquestions'] = 'No generated questions are available yet.';
$string['numberofquestions'] = 'Number of questions';
$string['optionalservices'] = 'Branding, support and optional future services';
$string['optionalservices_desc'] = 'The plugin is free and needs no service license. These settings are retained only for explicitly enabled future services and never restrict core features.';
$string['optionalservicesdisabled'] = 'Optional external services are disabled. The free plugin and all core quiz-generation features remain available.';
$string['outputcost'] = 'Output cost per million tokens (USD)';
$string['outputtokens'] = 'Output tokens';
$string['pdftotextpath'] = 'Path to pdftotext';
$string['pdftotextpath_desc'] = 'Optional Poppler pdftotext executable path for PDF extraction.';

$string['pluginname'] = 'AI Quiz Generator';
$string['privacy:metadata:aiprovider'] = 'The configured AI provider receives selected course content to generate questions.';
$string['privacy:metadata:aiprovider:coursecontent'] = 'Cleaned content from teacher-selected course resources.';
$string['privacy:metadata:local_aiquizgen_jobs'] = 'Stores generation job ownership and settings.';
$string['privacy:metadata:local_aiquizgen_jobs:configjson'] = 'Quiz generation preferences.';
$string['privacy:metadata:local_aiquizgen_jobs:estimatedcost'] = 'Estimated monetary cost of the generation.';
$string['privacy:metadata:local_aiquizgen_jobs:inputtokens'] = 'Input token usage reported by the AI provider.';
$string['privacy:metadata:local_aiquizgen_jobs:model'] = 'AI model used for generation.';
$string['privacy:metadata:local_aiquizgen_jobs:outputtokens'] = 'Output token usage reported by the AI provider.';
$string['privacy:metadata:local_aiquizgen_jobs:userid'] = 'The teacher who started the job.';
$string['privacy:metadata:local_aiquizgen_questions'] = 'Stores AI-generated draft questions.';
$string['privacy:metadata:local_aiquizgen_questions:questiontext'] = 'Generated question text.';
$string['providertest'] = 'AI provider connection test';
$string['providertestbutton'] = 'Test AI connection';
$string['providertestcostwarning'] = 'This sends one small generation request and may incur a provider charge.';
$string['providertestsuccess'] = 'Connection succeeded. Provider reported {$a->input} input and {$a->output} output tokens.';
$string['publishquiz'] = 'Publish generated quiz';
$string['questions'] = 'Questions';
$string['questiontype'] = 'Question type';
$string['quizname'] = 'Quiz name';
$string['ratelimitreached'] = 'Your daily AI generation limit has been reached.';
$string['regenerate'] = 'Regenerate';
$string['retriedjob'] = 'Generation retry queued.';
$string['retry'] = 'Retry';
$string['sections'] = 'Course sections';
$string['source'] = 'Source';
$string['status'] = 'Status';
$string['supportemail'] = 'Support email';
$string['taskvalidatelicense'] = 'Validate AI Quiz Generator service license';
$string['totalcost'] = 'Estimated cost';
$string['usage'] = 'AI usage';
$string['usageoverview'] = 'Usage overview';
$string['vendorname'] = 'Vendor/brand name';
$string['view'] = 'View';
$string['zerounlimited'] = 'Use 0 for unlimited.';
