# Contributing

This package is intentionally small.

Good contributions keep it small. That is the whole trick.

## Scope

Good fits:

- shared reader contracts
- immutable value objects
- small parsing helpers used by multiple concrete readers
- charset aliases used in real FTN messages
- tests for edge cases in existing helpers
- documentation fixes

Usually not a fit:

- concrete Squish, JAM, Hudson, or other message-base readers
- Laravel service providers, facades, config publishing, or app bootstrapping
- database models or migrations
- framework-specific behavior
- broad abstractions without at least two real users

If a change needs a paragraph to justify why it belongs here, it probably belongs somewhere else.

## Development Setup

```bash
composer install
```

## Quality Gates

Run the full suite before opening a pull request:

```bash
composer test:all
```

For focused work:

```bash
composer test
composer test:types
composer test:refactor
```

## Coding Style

- Use strict types.
- Keep public APIs stable.
- Prefer final classes and readonly value objects.
- Add explicit return types.
- Keep helpers dependency-free.
- Do not edit `vendor/`.
- Keep `composer.lock` in sync when `composer.json` changes.

## Public API Changes

Be careful with these:

- interface signatures
- DTO constructor parameter order
- required DTO constructor parameters
- default option values

Those are breaking changes. Say so plainly in the pull request and changelog.

## Tests

Add tests for behavior, especially around:

- charset aliases
- unknown charset fallback behavior
- FTN control-line parsing
- null-padded field handling
- line-ending normalization

Tiny helpers still deserve tests. The bugs here are small, old, and annoying.

## Pull Requests

Use a clear title and explain:

- what changed
- why it belongs in this shared package
- whether public API changed
- which commands passed

Keep pull requests focused. Unrelated cleanup can wait.

## Security Reports

Do not report security issues in public tickets. Use the private reporting path in [SECURITY.md](SECURITY.md).
