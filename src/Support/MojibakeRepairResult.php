<?php

declare(strict_types=1);

namespace Golded\Ftn\Support;

final readonly class MojibakeRepairResult
{
    public function __construct(
        public string $text,
        public bool $changed,
        public float $confidence,
    ) {}
}
