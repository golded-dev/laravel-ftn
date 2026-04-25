<?php

declare(strict_types=1);

namespace Golded\Ftn;

use DateTimeInterface;

final readonly class ParsedMessage
{
    public function __construct(
        public int $msgno,
        public string $fromName,
        public string $toName,
        public string $subject,
        public string $bodyText,
        public int $attributesRaw,
        public ?DateTimeInterface $postedAt = null,
        public ?string $externalId = null,
        public ?string $fromAddress = null,
        public ?string $toAddress = null,
        public ?int $replyToMsgno = null,
        public ?int $reply1stMsgno = null,
        public ?int $replyNextMsgno = null,
        public ?string $areaCode = null,
        public ?string $areaName = null,
        public ?int $areaSortOrder = null,
        public ?string $areaMetaKey = null,
    ) {}
}
