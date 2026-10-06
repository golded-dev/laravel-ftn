<?php

declare(strict_types=1);

use Golded\Ftn\Support\CharsetDetector;
use Golded\Ftn\Support\Text;

it('defaults to CP850 when no CHRS kludge is present', function (): void {
    expect(CharsetDetector::detect("Hello world\nNo kludges here"))->toBe('CP850');
});

it('detects known FidoNet charset aliases', function (string $kludge, string $charset): void {
    expect(CharsetDetector::detect($kludge."\nBody"))->toBe($charset);
})->with([
    'IBMPC' => ["\x01CHRS: IBMPC 2", 'CP850'],
    'LATIN-1' => ["\x01CHRS: LATIN-1 2", 'ISO-8859-1'],
    'CHARSET' => ["\x01CHARSET: LATIN-1", 'ISO-8859-1'],
    'KOI8-R' => ["\x01CHRS: KOI8-R", 'KOI8-R'],
]);

it('uses the fallback for unrecognised charset names', function (): void {
    expect(CharsetDetector::detect("\x01CHRS: FIDOMAZ 2\nBody", 'CP437'))->toBe('CP437');
});

it('decodes DOS Cyrillic bytes labelled with historical GoldED aliases', function (string $alias): void {
    $bytes = "\x8f\xe0\xa8\xa2\xa5\xe2";
    $charset = CharsetDetector::detect("\x01CHRS: {$alias} 2\n".$bytes);

    expect($charset)->toBe('CP866')
        ->and(Text::toUtf8($bytes, $charset))->toBe('Привет');
})->with(['CP-866', '+7FIDO', '+7_FIDO', 'FIDO7', 'FIDO_7', 'RUS']);

it('decodes KOI8-R bytes labelled with historical GoldED aliases', function (string $alias): void {
    $bytes = "\xf0\xd2\xc9\xd7\xc5\xd4";
    $charset = CharsetDetector::detect("\x01CHARSET: {$alias}\n".$bytes);

    expect($charset)->toBe('KOI8-R')
        ->and(Text::toUtf8($bytes, $charset))->toBe('Привет');
})->with(['KOI', 'KOI8', 'GOST', 'CP20866']);

it('decodes Windows Cyrillic bytes labelled with historical GoldED aliases', function (string $alias): void {
    $bytes = "\xcf\xf0\xe8\xe2\xe5\xf2";
    $charset = CharsetDetector::detect("\x01chrs: {$alias} 2\n".$bytes);

    expect($charset)->toBe('CP1251')
        ->and(Text::toUtf8($bytes, $charset))->toBe('Привет');
})->with(['WIN', 'WIN-1251', 'WINDOWS-1251', 'CP-1251']);

it('keeps charset fallback and IBMPC decoding unchanged', function (): void {
    $bytes = "\x9b";

    expect(Text::toUtf8($bytes, CharsetDetector::detect('Body')))->toBe('ø')
        ->and(Text::toUtf8($bytes, CharsetDetector::detect("\x01CHRS: IBMPC 2")))->toBe('ø')
        ->and(Text::toUtf8($bytes, CharsetDetector::detect("\x01CHRS: mystery 2", 'ISO-8859-1')))->toBe("\u{009b}")
        ->and(CharsetDetector::detect("\x01charset: win-1251"))->toBe('CP1251')
        ->and(CharsetDetector::detect("\x01CHRS: WINDOWS-1252 2", 'CP437'))->toBe('CP437');
});

it('uses the first FTN charset declaration rather than MIME or a later declaration', function (): void {
    $body = "Content-Type: text/plain; charset=UTF-8\n\x01CHARSET: koi8\n\x01CHRS: WIN 2\nBody";

    expect(CharsetDetector::detect($body))->toBe('KOI8-R');
});


it('decodes Ukrainian KOI8-U bytes labelled with GoldED aliases', function (string $alias): void {
    // Literal bytes from Unicode's KOI8-U mapping, including all Ukrainian letters.
    $bytes = "\xbd\xad\xb4\xa4\xb6\xa6\xb7\xa7";
    $charset = CharsetDetector::detect("\x01CHARSET: {$alias}\n".$bytes);

    expect($charset)->toBe('KOI8-U')
        ->and(Text::toUtf8($bytes, $charset))->toBe('ҐґЄєІіЇї');
})->with(['KOI8-U', 'KOI8U', 'KOU', 'KOI-U', 'CP21866']);


it('decodes Ukrainian DOS bytes labelled with GoldED aliases', function (string $alias): void {
    // CP1125's Ukrainian extension occupies F2–F9, unlike CP866.
    $bytes = "\xf2\xf3\xf4\xf5\xf6\xf7\xf8\xf9";
    $charset = CharsetDetector::detect("\x01CHRS: {$alias} 2\n".$bytes);

    expect($charset)->toBe('CP1125')
        ->and(Text::toUtf8($bytes, $charset))->toBe('ҐґЄєІіЇї');
})->with(['CP1125', 'UKR']);
