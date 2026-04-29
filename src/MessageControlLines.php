<?php

declare(strict_types=1);

namespace Golded\Ftn;

final readonly class MessageControlLines
{
    /**
     * @param list<ControlLine> $kludges
     * @param list<string> $seenBy
     * @param list<string> $path
     */
    public function __construct(
        public array $kludges = [],
        public ?string $msgid = null,
        public ?string $reply = null,
        public ?string $charset = null,
        public array $seenBy = [],
        public array $path = [],
        public ?string $tearline = null,
        public ?string $origin = null,
        public ?FtnAddress $originAddress = null,
    ) {}
}
