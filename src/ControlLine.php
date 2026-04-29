<?php

declare(strict_types=1);

namespace Golded\Ftn;

final readonly class ControlLine
{
    public function __construct(
        public string $name,
        public string $value,
        public string $raw,
    ) {}
}
