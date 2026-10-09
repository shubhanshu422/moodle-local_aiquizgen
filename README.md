# AI Quiz Generator (`local_aiquizgen`)

AI Quiz Generator is a free public-beta Moodle local plugin. It scans teacher-selected course content, generates source-grounded multiple-choice questions through an administrator-configured AI provider, and publishes approved questions to Moodle's Question Bank and Quiz activity.

No purchase, product key, vendor account, or licensing server is required. Administrators supply their own compatible AI API key and are responsible for any charges from that AI provider.

## Features

- Extracts Pages, Book chapters, Labels, Lessons, Assignment descriptions, text resources, and PDFs
- Processes large courses in background tasks and chunks source content before AI calls
- Requires source evidence, validates responses, and rejects duplicates
- Supports preview, edit, regenerate, approve, and delete before publication
- Creates sequential `Course Name - Practice Quiz N` quizzes
- Publishes into Moodle's native shared Question Bank and Quiz APIs
- Provides generation history, retries, token/API-call usage, estimated cost, and configurable limits
- Keeps AI credentials server-side and checks Moodle capabilities, sessions, parameters, and contexts

## Compatibility and beta status

- Requires Moodle 4.5 or later
- Tested on Moodle 5.0.10, PHP 8.2, and MariaDB 10.11
- Other Moodle-supported PHP/database combinations have not yet been fully tested

Version 0.3.0-free-beta.2 is suitable for evaluation and limited public beta use. Fresh installation and upgrade were tested on Moodle 5.0.10, PHP 8.2.12, and MariaDB 10.11.16. It is not represented as a production-certified or fully supported commercial release. Report bugs through the distributor's issue tracker or the support contact configured by the site administrator; do not include API keys, passwords, personal data, or private course content.

See [Installation](INSTALLATION.md), [Configuration](CONFIGURATION.md), [Licensing](LICENSING.md), [Privacy](PRIVACY.md), [Troubleshooting](TROUBLESHOOTING.md), [Changelog](CHANGELOG.md), and the [release checklist](COMMERCIAL_RELEASE_CHECKLIST.md).

Licensed under GNU GPL v3 or later.
