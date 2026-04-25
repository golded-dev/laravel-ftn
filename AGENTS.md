# Agent Instructions

## Project Shape
- This is `golded-dev/laravel-ftn`, a small PHP 8.4 library.
- Purpose: shared FTN/FidoNet message-base reader contracts, immutable value objects, and low-level parsing helpers.
- Namespace: `Golded\Ftn\`.
- Despite the name, this is not a Laravel app. Do not add service providers, config publishing, facades, container assumptions, or other framework furniture unless the package explicitly grows that surface.

## Boundaries
- Keep this package boring and portable.
- Concrete message-base readers belong elsewhere unless the task explicitly asks to add one here.
- `src/Contracts` defines reader/locator interfaces.
- `src/ParsedArea.php`, `src/ParsedMessage.php`, and `src/ReaderOptions.php` are lightweight immutable DTOs.
- `src/Support` is for small, dependency-free helpers around FTN text, control lines, charsets, null-padded fields, and synthetic IDs.
- Avoid runtime dependencies. Right now this package requires only PHP. Keep it that way unless there is a real reason.

## Coding Style
- Use strict types in every PHP file.
- Follow the existing style: final classes, readonly DTOs, constructor property promotion, explicit return types.
- Prefer static helper methods only for small pure operations. If state appears, stop and check whether it belongs in a reader implementation package instead.
- Keep names literal. FTN formats are already weird enough; clever naming is unpaid technical debt.
- Preserve public API compatibility unless the task is explicitly a breaking change.
- Do not silently change defaults like `ReaderOptions::$fallbackCharset = 'CP850'`.

## FTN Parsing Notes
- FidoNet control lines may start with `\x01`. They are often called kludges, because of course they are.
- `CHRS` and `CHARSET` values are aliases, not guaranteed canonical names.
- Null-padded fields matter. Use `Text::readNullPaddedField()` instead of ad hoc trimming when reading fixed-width binary fields.
- Normalize line endings for message bodies, but avoid destroying meaningful control-line content unless the caller asked for display text.
- Treat `MSGID` as external identity when present; use synthetic IDs only as a fallback.
- Old encodings are part of the domain, not a bug. Preserve existing aliases when adding charset handling.

## Tests And Quality Gates
- Run the focused test first when touching a narrow helper:
  - `vendor/bin/pest tests/Unit/CharsetDetectorTest.php`
- Run the full suite before handing off code changes:
  - `composer test:all`
- The Composer scripts are:
  - `composer test`
  - `composer test:types`
  - `composer test:refactor`
  - `composer test:all`
- PHPStan is configured at max level through `phpstan.neon`.
- Rector uses `odinns/coding-style`; do not fight it by hand-formatting around it.

## Dependency And File Hygiene
- Do not edit `vendor/`.
- Do not commit generated caches or local artifacts.
- Keep `composer.lock` in sync if `composer.json` changes.
- `CLAUDE.md` and `GEMINI.md` should remain symlinks to `AGENTS.md`.

## When Changing Public Types
- Think about downstream readers before adding constructor fields or interface methods.
- Adding optional DTO fields is usually fine.
- Changing constructor order, required fields, or interface signatures is breaking. Call it out plainly.
- Add tests around behavior, not just structure.

## Review Bias
- Watch for scope creep. This repo should stay a thin shared contract layer.
- Watch for encoding assumptions. UTF-8-only thinking will lie to you here.
- Watch for parser helpers that accidentally become full parsers. That part smells a bit; move carefully.
