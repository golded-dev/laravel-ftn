# Changelog

Notable changes to `golded-dev/laravel-ftn`.

This project uses semantic versioning.

## 1.0.0 - 2026-04-25

Initial stable release.

### Added

- Add shared `MessageBaseReader` and `MessageSourceLocator` contracts.
- Add immutable `ParsedMessage`, `ParsedArea`, and `ReaderOptions` value objects.
- Add charset detection for common FTN `CHRS` and `CHARSET` aliases.
- Add helpers for control-line extraction, null-padded fields, body normalization, UTF-8 conversion, and synthetic IDs.
- Add Pest, PHPStan, and Rector quality gates.
- Add public package documentation, security policy, code of conduct, and CI workflow.
