# Troubleshooting

- **No questions:** verify selected content contains sufficient factual text and run Moodle cron.
- **Failed AI job:** open Generation History, inspect the escaped error, confirm endpoint/model/key, then retry.
- **Zero cost:** configure current input/output prices; old provider usage cannot be reconstructed.
- **PDF empty:** install `pdftotext` and configure its executable path.
- **Session partition full:** free disk space in the Moodle data partition; do not delete `filedir` or database files manually.
- **License server unavailable:** core functionality continues. Confirm HTTPS, DNS, firewall, key, and public signature key.
- **Invalid signature:** ensure the configured public key matches the license server signing key. Never copy the private key to Moodle.
- **Background job remains queued:** run `php admin/cli/cron.php` and inspect scheduled-task logs.

When requesting support, include Moodle/PHP/plugin versions and sanitized logs. Never send API keys, license keys, passwords, or private signing keys.

