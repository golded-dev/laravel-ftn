<?php

declare(strict_types=1);

namespace Golded\Ftn;

final readonly class ParsedArea
{
    public function __construct(
        public string $code,
        public string $name,
        public string $sourceType,
        public int $sortOrder = 0,
        public ?string $metaKey = null,
    ) {}
}
