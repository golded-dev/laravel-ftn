<?php

declare(strict_types=1);

namespace Golded\Ftn;

use DateTimeInterface;

final readonly class OutgoingMessage
{
    /**
     * @param list<ControlLine> $controlLines
     */
    public function __construct(
        public string $fromName,
        public string $toName,
        public string $subject,
        public string $bodyText,
        public ?string $externalId = null,
        public ?FtnAddress $fromAddress = null,
        public ?FtnAddress $toAddress = null,
        public ?DateTimeInterface $postedAt = null,
        public ?int $attributesRaw = null,
        public array $controlLines = [],
        public ?MessageProvenance $provenance = null,
    ) {}
}
