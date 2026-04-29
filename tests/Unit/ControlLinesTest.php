<?php

declare(strict_types=1);

use Golded\Ftn\Support\ControlLines;

it('keeps existing MSGID extraction behavior', function (): void {
    expect(ControlLines::extractMsgid("\x01MSGID: 2:236/77 abc123\r\nBody"))->toBe('2:236/77 abc123')
        ->and(ControlLines::extractMsgid("Body\nNo msgid"))->toBeNull();
});

it('parses common hidden kludges', function (): void {
    $controlLines = ControlLines::parseMessage(implode("\n", [
        "\x01MSGID: 2:236/77 abc123",
        "\x01REPLY: 2:236/77 def456",
        "\x01CHRS: LATIN-1 2",
        "\x01CHARSET: UTF-8",
        'Body',
    ]));

    $firstKludge = $controlLines->kludges[0] ?? null;

    expect($controlLines->kludges)->toHaveCount(4)
        ->and($firstKludge)->not->toBeNull()
        ->and($firstKludge?->name)->toBe('MSGID')
        ->and($firstKludge?->value)->toBe('2:236/77 abc123')
        ->and($firstKludge?->raw)->toBe("\x01MSGID: 2:236/77 abc123")
        ->and($controlLines->msgid)->toBe('2:236/77 abc123')
        ->and($controlLines->reply)->toBe('2:236/77 def456')
        ->and($controlLines->charset)->toBe('LATIN-1 2');
});

it('uses CHARSET when CHRS is not present', function (): void {
    $controlLines = ControlLines::parseMessage("\x01CHARSET: UTF-8\nBody");

    expect($controlLines->charset)->toBe('UTF-8');
});

it('parses seen-by and path values as raw routing strings', function (): void {
    $controlLines = ControlLines::parseMessage(implode("\n", [
        'SEEN-BY: 236/77 100 101',
        'PATH: 236/77 100/1',
        'SEEN-BY: 2:236/77',
    ]));

    expect($controlLines->seenBy)->toBe(['236/77 100 101', '2:236/77'])
        ->and($controlLines->path)->toBe(['236/77 100/1']);
});

it('parses tearline and origin details', function (): void {
    $controlLines = ControlLines::parseMessage(implode("\n", [
        '--- GoldED+/LNX 1.1.5',
        ' * Origin: The tiny board with opinions (2:236/77.1@fidonet)',
    ]));

    expect($controlLines->tearline)->toBe('--- GoldED+/LNX 1.1.5')
        ->and($controlLines->origin)->toBe('The tiny board with opinions (2:236/77.1@fidonet)')
        ->and($controlLines->originAddress?->toString())->toBe('2:236/77.1@fidonet');
});

it('does not extract abbreviated origin addresses', function (): void {
    $controlLines = ControlLines::parseMessage(' * Origin: Local echo (236/77)');

    expect($controlLines->origin)->toBe('Local echo (236/77)')
        ->and($controlLines->originAddress)->toBeNull();
});

it('keeps the first tearline and origin when multiples are present', function (): void {
    $controlLines = ControlLines::parseMessage(implode("\n", [
        '--- First',
        '--- Second',
        ' * Origin: First board (2:236/77)',
        ' * Origin: Second board (2:236/78)',
    ]));

    expect($controlLines->tearline)->toBe('--- First')
        ->and($controlLines->origin)->toBe('First board (2:236/77)')
        ->and($controlLines->originAddress?->toString())->toBe('2:236/77');
});

it('ignores quoted control-line examples', function (): void {
    $controlLines = ControlLines::parseMessage(implode("\n", [
        '> SEEN-BY: 236/77',
        'OD> PATH: 236/77',
        sprintf('ABCD12> %sMSGID: 2:236/77 quoted', "\x01"),
        '>  * Origin: Quoted board (2:236/77)',
        'SEEN-BY: 236/100',
    ]));

    expect($controlLines->msgid)->toBeNull()
        ->and($controlLines->path)->toBe([])
        ->and($controlLines->origin)->toBeNull()
        ->and($controlLines->seenBy)->toBe(['236/100']);
});
