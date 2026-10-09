# Installation

## Requirements

- Moodle 4.5 or later (tested on Moodle 5.0.10)
- A PHP version supported by that Moodle release (tested with PHP 8.2)
- A Moodle-supported database (tested with MariaDB 10.11)
- Moodle cron running at least once per minute
- Your own OpenAI-compatible AI API key
- Optional Poppler `pdftotext` executable for PDF extraction

## Install or upgrade

1. Back up the Moodle database, code, and `moodledata` before changing a production site.
2. Extract the ZIP so the plugin is exactly `<moodle>/local/aiquizgen`.
3. Visit **Site administration > Notifications**, or run `php admin/cli/upgrade.php --non-interactive`.
4. Run `php admin/cli/purge_caches.php`.
5. Configure **Site administration > Plugins > Local plugins > AI Quiz Generator**.
6. Confirm cron is active with `php admin/cli/cron.php`.

The plugin is free and requires no activation, product key, vendor account, or external licensing service. Fresh installation and upgrade from 0.3.0-free-beta.1 were tested for 0.3.0-free-beta.2. Existing configuration and plugin data are retained.
