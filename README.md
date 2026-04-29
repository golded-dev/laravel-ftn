# Laravel FTN

Shared FTN/FidoNet reader contracts, value objects, and small parsing helpers for PHP 8.4.

Concrete reader packages use this as their common language.

It does not read Squish, JAM, Hudson, or any other message base by itself. That is deliberate. This package is the shared core, not the reader.

## Installation

```bash
composer require golded-dev/laravel-ftn:^1.0
```

Requires PHP 8.4+.

## What You Get

- `MessageBaseReader`: interface for classes that read messages from a source path.
- `MessageSourceCatalog`: interface for classes that list readable message sources.
- `MessageWriter`: interface for classes that write outgoing messages.
- `MessageSourceLocator`: interface for resolving a message source path.
- `FtnAddress`: readonly value object for full FTN addresses.
- `ParsedMessage`: readonly value object for normalized message data.
- `ParsedArea`: readonly value object for message area metadata.
- `OutgoingMessage`: readonly value object for messages handed to writers.
- `ReaderOptions`: shared reader options.
- `WriterOptions`: shared writer options.
- `MessageControlLines`: parsed FTN control-line metadata.
- `MessageProvenance`: source metadata for imported messages.
- `CharsetDetector`: detects FTN charset kludges such as `CHRS` and `CHARSET`.
- `MojibakeRepairer`: repairs common FTN mojibake when the signal is strong enough.
- `ControlLines`: extracts selected FTN control lines.
- `Text`: helper methods for null-padded fields, body normalization, encoding conversion, and synthetic IDs.

## What You Do Not Get

- No concrete message-base reader.
- No concrete message writer.
- No Laravel service provider.
- No database models.
- No queues, commands, config publishing, or framework bootstrapping.

If you need a real reader, build it in a package that depends on this one.

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

Use `MessageSourceCatalog` when a reader package can list areas, folders, or other readable sources:

```php
<?php

declare(strict_types=1);

use Golded\Ftn\Contracts\MessageSourceCatalog;
use Golded\Ftn\MessageSource;
use Golded\Ftn\ReaderOptions;

final class ExampleCatalog implements MessageSourceCatalog
{
    /**
     * @return iterable<MessageSource>
     */
    public function sources(string $path, ?ReaderOptions $options = null): iterable
    {
        yield new MessageSource(
            sourceType: 'example',
            path: $path.'/general',
            code: 'GENERAL',
            name: 'General',
            sortOrder: 10,
            metaKey: 'example:general',
        );
    }
}
```

Consumers should depend on the contracts and DTOs, not on a concrete storage format.

## Reader Options

```php
use Golded\Ftn\ReaderOptions;

$options = new ReaderOptions(
    fallbackCharset: 'CP850',
);
```

`CP850` is the default fallback because old FTN message bases are not UTF-8-first.

## FTN Addresses

```php
use Golded\Ftn\FtnAddress;

$address = FtnAddress::fromString('2:236/77.1@fidonet');

$address->zone; // 2
$address->net; // 236
$address->node; // 77
$address->point; // 1
$address->domain; // fidonet
$address->toString(); // 2:236/77.1@fidonet
```

`fromString()` throws when the address is invalid. `tryFromString()` returns `null`.

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

Reader packages may attach `MessageControlLines` and `MessageProvenance` when the source format exposes them.

## Writing Messages

`MessageWriter` is a contract only. This package defines the shape of outgoing messages, not how a format stores them.

```php
<?php

declare(strict_types=1);

use Golded\Ftn\Contracts\MessageWriter;
use Golded\Ftn\OutgoingMessage;
use Golded\Ftn\WriterOptions;

final class ExampleWriter implements MessageWriter
{
    /**
     * @param iterable<OutgoingMessage> $messages
     */
    public function write(string $path, iterable $messages, ?WriterOptions $options = null): int
    {
        $written = 0;

        foreach ($messages as $message) {
            $written++;
        }

        return $written;
    }
}
```

Concrete packages own charset conversion, wrapping, storage rules, and format details.

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

## Mojibake Repair

FTN messages often contain text decoded through the wrong charset. `MojibakeRepairer` fixes the common cases when the repaired text clearly scores better than the original.

```php
use Golded\Ftn\Support\MojibakeRepairer;

$result = MojibakeRepairer::repair('Bruger m°de');

$result->text; // Bruger møde
$result->changed; // true
$result->confidence; // 0.0-1.0
```

It handles DOS glyph damage, UTF-8-as-Latin-1 damage, and RFC 2047 encoded words.

It does not decide when a UI should apply repairs, expose toggles, or rewrite stored source text. That belongs in the consuming app.

## Text Helpers

```php
use Golded\Ftn\Support\Text;

$name = Text::readNullPaddedField($rawHeader, offset: 0, length: 36);
$body = Text::parseBody($rawBody);
$utf8 = Text::toUtf8($rawSubject, 'CP850');
$id = Text::syntheticId($from, $to, $subject, $date, $body);
```

Use these helpers instead of scattering string parsing through concrete readers.

## Control Lines

```php
use Golded\Ftn\Support\ControlLines;

$msgid = ControlLines::extractMsgid($rawBody);
```

`extractMsgid()` returns the trimmed `MSGID` value, or `null`.

Use `parseMessage()` when you need the structural control-line metadata:

```php
$controlLines = ControlLines::parseMessage($rawBody);

$controlLines->msgid;
$controlLines->reply;
$controlLines->charset;
$controlLines->seenBy;
$controlLines->path;
$controlLines->tearline;
$controlLines->origin;
$controlLines->originAddress?->toString();
```

`parseMessage()` does not convert charsets, rewrite the body, or expand abbreviated routing lines. `SEEN-BY` and `PATH` values stay raw because resolving them needs source context.

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
