<?php

declare(strict_types=1);

namespace Golded\Ftn\Contracts;

use Golded\Ftn\OutgoingMessage;
use Golded\Ftn\WriterOptions;

interface MessageWriter
{
    /**
     * @param iterable<OutgoingMessage> $messages
     */
    public function write(string $path, iterable $messages, ?WriterOptions $options = null): int;
}
