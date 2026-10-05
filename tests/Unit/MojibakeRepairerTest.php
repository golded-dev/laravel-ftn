<?php

declare(strict_types=1);

use Golded\Ftn\Support\MojibakeRepairer;

it('repairs FTN subject text damaged by DOS glyph display', function (string $damaged, string $repaired): void {
    $result = MojibakeRepairer::repair($damaged);

    expect($result->changed)->toBeTrue()
        ->and($result->text)->toBe($repaired)
        ->and($result->confidence)->toBeGreaterThan(0.0);
})->with([
    'Danish meeting' => ['Bruger m°de', 'Bruger møde'],
    'Swedish upgrade' => ['Uppgradering av min nyckel f÷r GoldED', 'Uppgradering av min nyckel för GoldED'],
    'Danish queue' => [
        'imageclub: K° pÕ indkommende mail til mail.image.dk (┼ben)',
        'imageclub: Kø på indkommende mail til mail.image.dk (Åben)',
    ],
]);

it('repairs quoted lines with a lower threshold', function (string $damaged, string $repaired): void {
    $result = MojibakeRepairer::repair($damaged);

    expect($result->changed)->toBeTrue()
        ->and($result->text)->toBe($repaired);
})->with([
    'sharp s' => ['AB> da▀', 'AB> daß'],
    'umlaut sharp s' => ['AB> m³▀te', 'AB> müßte'],
    'o umlaut' => ['AB> geh÷rt', 'AB> gehört'],
    'a umlaut' => ['AB> geõndert', 'AB> geändert'],
    'leading umlaut' => ['AB> ³berflogen', 'AB> überflogen'],
]);

it('repairs UTF-8 bytes displayed as Latin-1', function (): void {
    $result = MojibakeRepairer::repair('SÃ¥dan gÃ¸r vi');

    expect($result->changed)->toBeTrue()
        ->and($result->text)->toBe('Sådan gør vi');
});

it('decodes RFC 2047 encoded words before scoring', function (): void {
    $result = MojibakeRepairer::repair('=?ISO-8859-1?Q?Bruger_m=F8de?=');

    expect($result->changed)->toBeTrue()
        ->and($result->text)->toBe('Bruger møde');
});

it('leaves clean UTF-8 text unchanged', function (): void {
    $result = MojibakeRepairer::repair('Bruger møde på lørdag');

    expect($result->changed)->toBeFalse()
        ->and($result->text)->toBe('Bruger møde på lørdag')
        ->and($result->confidence)->toBe(0.0);
});

it('leaves plain ASCII text unchanged', function (): void {
    $result = MojibakeRepairer::repair('Hello world');

    expect($result->changed)->toBeFalse()
        ->and($result->text)->toBe('Hello world');
});

it('leaves low confidence text unchanged', function (): void {
    $result = MojibakeRepairer::repair('Price 10°');

    expect($result->changed)->toBeFalse()
        ->and($result->text)->toBe('Price 10°');
});

it('preserves spaced temperatures', function (): void {
    $result = MojibakeRepairer::repair('20 °C');

    expect($result->text)->toBe('20 °C')
        ->and($result->changed)->toBeFalse();
});

it('repairs damage beside a literal degree', function (): void {
    expect(MojibakeRepairer::repair('m°de at 10°')->text)->toBe('møde at 10°');
});

it('rejects repairs that discard unsupported characters', function (string $text): void {
    $result = MojibakeRepairer::repair($text);

    expect($result->text)->toBe($text)
        ->and($result->changed)->toBeFalse()
        ->and($result->confidence)->toBe(0.0);
})->with(['SÃ¥dan ☃', 'Bruger m°de 🙂']);

it('preserves literal degree contexts including quoted lines', function (string $text): void {
    $result = MojibakeRepairer::repair($text);

    expect($result->text)->toBe($text)
        ->and($result->changed)->toBeFalse()
        ->and($result->confidence)->toBe(0.0);
})->with(['35°45', '-10°', '2°r', '20 °', "20\t°", '°C', 'ca. °F', 'ca. ° C', "°\tF", 'AB> 20 °C']);

it('repairs mixed encodings without extending degree contexts', function (string $damaged, string $expected): void {
    expect(MojibakeRepairer::repair($damaged)->text)->toBe($expected);
})->with([
    ['SÃ¥dan at 10°', 'Sådan at 10°'],
    ['AB> m°de at 10°', 'AB> møde at 10°'],
    ['m°de at 20 °C', 'møde at 20 °C'],
    ["2\nm°de", "2\nmøde"],
    ["2\n°l", "2\nøl"],
    ['°Code', 'øCode'],
]);
