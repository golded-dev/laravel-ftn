<?php

declare(strict_types=1);

namespace Golded\Ftn;

final readonly class MessageProvenance
{
    public function __construct(
        public string $sourceType,
        public ?string $sourcePath = null,
        public ?string $sourceId = null,
        public ?int $sourceOffset = null,
    ) {}
}
