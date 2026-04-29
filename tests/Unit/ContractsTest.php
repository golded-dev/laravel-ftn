<?php

declare(strict_types=1);

use Golded\Ftn\Contracts\MessageBaseReader;
use Golded\Ftn\Contracts\MessageSourceCatalog;
use Golded\Ftn\Contracts\MessageWriter;
use Golded\Ftn\MessageSource;
use Golded\Ftn\OutgoingMessage;
use Golded\Ftn\ParsedMessage;
use Golded\Ftn\ReaderOptions;
use Golded\Ftn\WriterOptions;

it('lets consumers list sources and read messages without knowing the format', function (): void {
    $catalog = new class implements MessageSourceCatalog
    {
        public function sources(string $path, ?ReaderOptions $options = null): iterable
        {
            yield new MessageSource(
                sourceType: 'fake',
                path: $path.'/general',
                code: 'GENERAL',
                name: 'General',
                sortOrder: 10,
                metaKey: 'fake:general',
            );
        }
    };

    $reader = new class implements MessageBaseReader
    {
        public function read(string $path, ?ReaderOptions $options = null): iterable
        {
            yield new ParsedMessage(
                msgno: 1,
                fromName: 'Sysop',
                toName: 'All',
                subject: 'Hello',
                bodyText: 'Message body',
                attributesRaw: 0,
                externalId: 'fake:1',
                areaCode: 'GENERAL',
                areaName: 'General',
            );
        }
    };

    $sources = iterator_to_array($catalog->sources('/bbs/messages'));
    $source = $sources[0] ?? null;
    $messages = $source === null ? [] : iterator_to_array($reader->read($source->path));
    $message = $messages[0] ?? null;

    expect($sources)->toHaveCount(1)
        ->and($source?->code)->toBe('GENERAL')
        ->and($messages)->toHaveCount(1)
        ->and($message?->subject)->toBe('Hello');
});

it('lets writers accept outgoing messages and return a written count', function (): void {
    $writer = new class implements MessageWriter
    {
        public function write(string $path, iterable $messages, ?WriterOptions $options = null): int
        {
            return count(iterator_to_array($messages));
        }
    };

    $count = $writer->write('/bbs/out', [
        new OutgoingMessage(
            fromName: 'Alice',
            toName: 'Bob',
            subject: 'Ping',
            bodyText: 'Hello',
        ),
    ]);

    expect($count)->toBe(1);
});
