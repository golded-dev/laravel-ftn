# Changelog

Notable changes to `golded-dev/laravel-ftn`.

This project uses semantic versioning.

## 1.2.0 - 2026-04-29

### Added

- Add `FtnAddress` for full FTN address parsing and formatting.
- Add control-line DTOs and `ControlLines::parseMessage()` for kludges, seen-by, path, tearline, origin, and origin address metadata.
- Add message source, provenance, writer option, and outgoing message DTOs.
- Add `MessageSourceCatalog` and `MessageWriter` contracts.

### Changed

- Extend `ParsedMessage` with optional control-line metadata and provenance fields.

## 1.1.1 - 2026-04-25

### Changed

- Use `actions/checkout@v5` in CI.
- Tighten README wording around package scope and mojibake repair.

## 1.1.0 - 2026-04-25

### Added

- Add `MojibakeRepairer` and `MojibakeRepairResult` for FTN text repair.
- Add coverage for DOS glyph damage, UTF-8-as-Latin-1 damage, RFC 2047 encoded words, and low-confidence no-op behavior.

## 1.0.0 - 2026-04-25

Initial stable release.

### Added

- Add shared `MessageBaseReader` and `MessageSourceLocator` contracts.
- Add immutable `ParsedMessage`, `ParsedArea`, and `ReaderOptions` value objects.
- Add charset detection for common FTN `CHRS` and `CHARSET` aliases.
- Add helpers for control-line extraction, null-padded fields, body normalization, UTF-8 conversion, and synthetic IDs.
- Add Pest, PHPStan, and Rector quality gates.
- Add public package documentation, security policy, code of conduct, and CI workflow.
