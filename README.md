# Laravel FTN

Shared FTN/FidoNet message-base reader contracts, value objects, and small parsing helpers for PHP 8.4.

This package gives concrete FTN reader packages the same basic vocabulary.

It does not read Squish, JAM, Hudson, or any other message base by itself. That is the point. This is the boring bit everything else can agree on.

## Installation

```bash
composer require golded-dev/laravel-ftn:^1.0
```

Requires PHP 8.4+.

## What You Get

- `MessageBaseReader`: interface for classes that read messages from a source path.
- `MessageSourceLocator`: interface for resolving a message source path.
- `ParsedMessage`: readonly value object for normalized message data.
- `ParsedArea`: readonly value object for message area metadata.
- `ReaderOptions`: shared reader options.
- `CharsetDetector`: detects FTN charset kludges such as `CHRS` and `CHARSET`.
- `ControlLines`: extracts selected FTN control lines.
- `Text`: helper methods for null-padded fields, body normalization, encoding conversion, and synthetic IDs.

## What You Do Not Get

- No concrete message-base reader.
- No Laravel service provider.
- No database models.
- No queues, commands, config publishing, or framework bootstrapping.

If you need a real reader, build it in a package that depends on this one. This is the contract layer, not the whole archaeology department.

## Reading Messages

Concrete reader packages implement `MessageBaseReader`.

```php
<?php

declare(strict_types=1);

use Golded\Ftn\Contracts\MessageBaseReader;
use Golded\Ftn\ParsedMessage;
use Golded\Ftn\ReaderOptions;

final class ExampleReader implements MessageBaseReader
{
    /**
     * @return iterable<ParsedMessage>
     */
    public function read(string $path, ?ReaderOptions $options = null): iterable
    {
        $options ??= new ReaderOptions();

        yield new ParsedMessage(
            msgno: 1,
            fromName: 'Sysop',
            toName: 'All',
            subject: 'Hello',
            bodyText: 'Message body',
            attributesRaw: 0,
            externalId: 'example:1',
            areaCode: 'GENERAL',
            areaName: 'General',
        );
    }
}
```

## Reader Options

```php
use Golded\Ftn\ReaderOptions;

$options = new ReaderOptions(
    fallbackCharset: 'CP850',
);
```

`CP850` is the default fallback because old FTN message bases do not care about your modern UTF-8 feelings.

## Parsed Messages

`ParsedMessage` is a readonly DTO. Reader implementations fill what the source format knows and leave unknown optional fields as `null`.

```php
use Golded\Ftn\ParsedMessage;

$message = new ParsedMessage(
    msgno: 42,
    fromName: 'Alice',
    toName: 'Bob',
    subject: 'Re: routing',
    bodyText: "Seen-by lines removed elsewhere\n",
    attributesRaw: 0,
    externalId: '2:203/0 12345678',
    fromAddress: '2:203/0',
    toAddress: '2:203/1',
    areaCode: 'NETMAIL',
    areaName: 'Netmail',
);
```

## Charset Detection

FTN messages often carry charset information in control lines:

```php
use Golded\Ftn\Support\CharsetDetector;

$charset = CharsetDetector::detect("\x01CHRS: LATIN-1 2\nBody");

// ISO-8859-1
```

Unknown charset names use the configured fallback:

```php
$charset = CharsetDetector::detect("\x01CHRS: MYSTERY\nBody", 'CP437');

// CP437
```

## Text Helpers

```php
use Golded\Ftn\Support\Text;

$name = Text::readNullPaddedField($rawHeader, offset: 0, length: 36);
$body = Text::parseBody($rawBody);
$utf8 = Text::toUtf8($rawSubject, 'CP850');
$id = Text::syntheticId($from, $to, $subject, $date, $body);
```

Use these helpers instead of scattering string surgery through concrete readers.

## Control Lines

```php
use Golded\Ftn\Support\ControlLines;

$msgid = ControlLines::extractMsgid($rawBody);
```

`extractMsgid()` returns the trimmed `MSGID` value, or `null`.

## Development

Install dependencies:

```bash
composer install
```

Run tests:

```bash
composer test
```

Run static analysis:

```bash
composer test:types
```

Run Rector dry-run:

```bash
composer test:refactor
```

Run everything:

```bash
composer test:all
```

## Versioning

This package starts at `1.0.0` and uses semantic versioning.

Breaking changes include:

- changing public interface signatures
- changing required DTO constructor parameters
- changing constructor parameter order
- changing default reader options

Adding optional DTO fields is usually a minor release.

## Contributing

Contributions are welcome when they keep the package small and portable. See [CONTRIBUTING.md](CONTRIBUTING.md).

## Security

Do not report security issues in public tickets. See [SECURITY.md](SECURITY.md).

## Code Of Conduct

Be direct, useful, and not a pain on purpose. See [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

Released under the MIT License. See [LICENSE](LICENSE).
