<?php

declare(strict_types=1);

namespace Golded\Ftn\Contracts;

use Golded\Ftn\MessageSource;
use Golded\Ftn\ReaderOptions;

interface MessageSourceCatalog
{
    /**
     * @return iterable<MessageSource>
     */
    public function sources(string $path, ?ReaderOptions $options = null): iterable;
}
