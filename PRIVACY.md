# Privacy

Selected course content is sent to the AI endpoint configured by the Moodle administrator. Generated drafts, teacher ownership, generation preferences, token usage, model, status, and estimated cost are stored in Moodle. Administrators must assess the provider's lawful basis, retention, subprocessors, international transfers, security, and deletion process before use.

The plugin's Moodle Privacy API implementation exports and deletes teacher-owned generation jobs and drafts. Published questions are handled by Moodle's native Question Bank privacy lifecycle.

No vendor account or licensing service is required. Optional future external-service integration is disabled by default and sends nothing while disabled. If explicitly enabled, it may send the configured service key, Moodle site URL, a deterministic hash of that URL, component name, and plugin version. It does not send course content or student data.
