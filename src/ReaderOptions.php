<?php

declare(strict_types=1);

namespace Golded\Ftn;

final readonly class ReaderOptions
{
    public function __construct(
        public string $fallbackCharset = 'CP850',
    ) {}
}
