<?php

declare(strict_types=1);

namespace Golded\Ftn;

final readonly class MessageSource
{
    public function __construct(
        public string $sourceType,
        public string $path,
        public string $code,
        public string $name,
        public int $sortOrder = 0,
        public ?string $metaKey = null,
    ) {}
}
