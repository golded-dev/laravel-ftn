<?php

declare(strict_types=1);

use Golded\Ftn\Support\Text;

it('decodes CP437 graphics and letters without substituting CP850', function (): void {
    expect(Text::toUtf8("\xda\xc4\xbf\n\xb3\x9b\xb3\n\xc0\xc4\xd9\x00\x00", 'CP437'))
        ->toBe("┌─┐\n│¢│\n└─┘");
});

it('accepts CP437 aliases case insensitively', function (string $charset): void {
    expect(Text::toUtf8("\x9b\x00", $charset))->toBe('¢');
})->with(['cp437', 'IBM437', 'ibm437']);

it('keeps mbstring substitution and default decoding unchanged', function (): void {
    expect(Text::toUtf8("\x9b\x00"))->toBe('ø')
        ->and(Text::toUtf8("\xff\x00", 'UTF-8'))->toBe('?');
});

it('keeps invalid charset errors instead of guessing', function (): void {
    expect(fn (): string => Text::toUtf8('Body', 'not-a-charset'))->toThrow(ValueError::class);
});

it('decodes Ukrainian CP1125 letters omitted from CP866', function (): void {
    expect(Text::toUtf8("\xf2\xf3\xf4\xf5\xf6\xf7\xf8\xf9\x00", 'CP1125'))
        ->toBe('ҐґЄєІіЇї');
});
