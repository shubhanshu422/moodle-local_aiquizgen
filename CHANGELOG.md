# Changelog

## 0.3.0-free-beta.2 - 2026-10-09

- Published generated questions, answers, and feedback as plain text to prevent externally generated markup from becoming executable quiz content.
- Strengthened AI-response validation for scalar fields and integer answer indexes.
- Treats a fully rejected AI response as insufficient source content instead of a successful empty generation.
- Added regression tests for grounding, malformed responses, and duplicate rejection.
- Brought the plugin source to a clean Moodle CodeSniffer result without suppressing findings.
- Redacted API credentials from provider errors and bounded exposed provider error text.
- Added graceful partial-chunk recovery while preserving failure when no valid questions remain.
- Added Moodle privacy export/deletion and plain-text formatting regression coverage.
- Corrected the generated Question Bank name encoding.
- Rebuilt the release with portable forward-slash ZIP entries and added repeatable build and validation scripts.

## 0.3.0-free-beta.1 - 2026-10-09

- Declared the plugin free for public beta use with no required payment, activation, vendor account, or licensing server.
- Disabled optional external-service integration by default and removed it from normal administration navigation.
- Kept all AI generation, source validation, history, usage, Question Bank, and Quiz functionality available without a service license.
- Clarified AI-provider charges, privacy, support, compatibility, installation, and beta limitations.
- Preserved optional signed-service client code for future separately enabled services.

## 0.3.0-beta.1 - 2026-10-09

- Added editable sequential quiz names and shared Question Bank publication.
- Added generation history, usage/cost tracking, limits, chunking, evidence validation, deduplication, and privacy handlers.
- Added optional signed external-service client and release documentation.

## 0.2.0 - 2026-10-09

- Added usage history, cost tracking, chunking, retry/delete controls, and usage limits.

## 0.1.0 - 2026-10-05

- Initial Moodle quiz-generation MVP.
