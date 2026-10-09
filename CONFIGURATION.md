# Configuration

## AI provider

Configure an OpenAI-compatible chat-completions endpoint, your own API key, and model. The key is read on the Moodle server and is not rendered into teacher/student pages or JavaScript. The plugin is free, but your AI provider may charge for requests.

Also configure source characters per request, maximum questions, per-teacher daily generation limit, current input/output price per million tokens, and optional `pdftotext`. Cost values are estimates only; provider invoices are authoritative.

## Branding and support

Vendor name, support email, and documentation URL are optional metadata. Never put secrets in these fields, documentation, source control, or a release ZIP.

## Optional future services

External service integration is disabled by default and is not needed for quiz generation. Leave it disabled for normal free-beta use. If an administrator explicitly enables it and configures a URL, key, and public verification key, the retained service client can exchange only the data listed in [PRIVACY.md](PRIVACY.md). Its state never enables, disables, or limits core plugin features.
