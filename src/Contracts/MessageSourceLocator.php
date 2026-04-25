<?php

declare(strict_types=1);

namespace Golded\Ftn\Contracts;

interface MessageSourceLocator
{
    public function find(string $path): ?string;
}
