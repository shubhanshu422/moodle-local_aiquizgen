# Update and Release Guide

1. Update the plugin version/release metadata and changelog.
2. Preserve the component name `local_aiquizgen` and provide upgrade steps for any schema change.
3. Run PHP syntax, PHPUnit/Behat, coding-standard, privacy, security, accessibility, clean-install, and upgrade checks where available.
4. Verify that core behavior works with optional external services disabled and no service key configured.
5. Build a ZIP with `tools/build_release.ps1 -OutputZip <candidate-path>`. The builder writes a single `aiquizgen/` root and portable `/` entry separators.
6. Run `tools/validate_release.ps1 -ZipPath <candidate-path>`, extract and inspect the ZIP, then publish its SHA-256 checksum.

Public-beta packages must identify uncompleted validation and must not claim production certification.
