<?php

declare(strict_types=1);

use Golded\Ftn\FtnAddress;

it('parses node addresses', function (): void {
    $address = FtnAddress::fromString('2:236/77');

    expect($address->zone)->toBe(2)
        ->and($address->net)->toBe(236)
        ->and($address->node)->toBe(77)
        ->and($address->point)->toBeNull()
        ->and($address->domain)->toBeNull()
        ->and($address->toString())->toBe('2:236/77');
});

it('parses point addresses', function (): void {
    $address = FtnAddress::fromString('2:236/77.1');

    expect($address->point)->toBe(1)
        ->and($address->toString())->toBe('2:236/77.1');
});

it('parses domain addresses', function (): void {
    $address = FtnAddress::fromString('2:236/77@fidonet');

    expect($address->domain)->toBe('fidonet')
        ->and($address->toString())->toBe('2:236/77@fidonet');
});

it('parses point addresses with domains', function (): void {
    $address = FtnAddress::fromString('2:236/77.1@fidonet');

    expect($address->point)->toBe(1)
        ->and($address->domain)->toBe('fidonet')
        ->and($address->toString())->toBe('2:236/77.1@fidonet');
});

it('throws for invalid addresses', function (): void {
    FtnAddress::fromString('236/77');
})->throws(InvalidArgumentException::class);

it('returns null when trying invalid addresses', function (): void {
    expect(FtnAddress::tryFromString('2:236'))->toBeNull();
});
