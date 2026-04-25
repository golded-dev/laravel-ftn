<?php

declare(strict_types=1);

namespace Golded\Ftn\Contracts;

use Golded\Ftn\ParsedMessage;
use Golded\Ftn\ReaderOptions;

interface MessageBaseReader
{
    /**
     * @return iterable<ParsedMessage>
     */
    public function read(string $path, ?ReaderOptions $options = null): iterable;
}
