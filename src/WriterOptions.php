<?php

declare(strict_types=1);

namespace Golded\Ftn;

final readonly class WriterOptions
{
    public function __construct(
        public string $targetCharset = 'CP850',
    ) {}
}
