# Free Public Beta Release Checklist

## Completed for this beta

- [x] Core operation does not require payment, activation, vendor account, license key, or licensing server
- [x] Optional external-service integration is disabled by default and does not gate core features
- [x] AI/API credentials remain server-side
- [x] Background generation, chunking, grounding validation, deduplication, history, usage, and limits are retained
- [x] Native Moodle Question Bank and Quiz publication are retained
- [x] Capability, session-key, parameter, context, and output controls are present
- [x] Moodle Privacy API export/delete implementation is present
- [x] Free-beta installation, configuration, licensing, privacy, troubleshooting, and compatibility are documented
- [x] Clean beta ZIP and archive-structure validation completed
- [x] Fresh installation and beta.1-to-beta.2 upgrade tested on Moodle 5.0.10/PHP 8.2.12/MariaDB 10.11.16
- [x] Plugin PHPUnit suite passed: 10 tests, 19 assertions

## Remaining before a production-certified release

- [ ] Add and run Behat browser scenarios; no browser-driver infrastructure was available for this audit
- [ ] Pass Moodle CodeSniffer/CodeChecker and official plugin validation (current CodeSniffer result: 143 errors and 48 warnings after 292 automatic fixes)
- [ ] Test fresh installs and upgrades across every advertised Moodle/PHP/database combination
- [ ] Complete accessibility, localization, security, and legal reviews
- [ ] Resolve host-level Moodle environment/security warnings documented by the site administrator

Future paid support, hosting, updates, or other services may be offered separately, but are not implemented or required by this free beta and must not restrict GPL rights or core functionality.
