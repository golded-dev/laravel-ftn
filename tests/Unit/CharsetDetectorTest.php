<?php

declare(strict_types=1);

use Golded\Ftn\Support\CharsetDetector;

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
