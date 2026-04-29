<?php

declare(strict_types=1);

namespace Golded\Ftn;

use InvalidArgumentException;

final readonly class FtnAddress
{
    public function __construct(
        public int $zone,
        public int $net,
        public int $node,
        public ?int $point = null,
        public ?string $domain = null,
    ) {}

    public static function fromString(string $value): self
    {
        $address = self::tryFromString($value);

        if (!$address instanceof FtnAddress) {
            throw new InvalidArgumentException("Invalid FTN address [{$value}].");
        }

        return $address;
    }

    public static function tryFromString(string $value): ?self
    {
        if (preg_match('/^(?<zone>\d+):(?<net>\d+)\/(?<node>\d+)(?:\.(?<point>\d+))?(?:@(?<domain>[A-Za-z0-9][A-Za-z0-9._-]*))?$/', trim($value), $matches) !== 1) {
            return null;
        }

        return new self(
            zone: (int) $matches['zone'],
            net: (int) $matches['net'],
            node: (int) $matches['node'],
            point: isset($matches['point']) && $matches['point'] !== '' ? (int) $matches['point'] : null,
            domain: $matches['domain'] ?? null,
        );
    }

    public function toString(): string
    {
        $value = "{$this->zone}:{$this->net}/{$this->node}";

        if ($this->point !== null) {
            $value .= ".{$this->point}";
        }

        if ($this->domain !== null) {
            $value .= "@{$this->domain}";
        }

        return $value;
    }
}
