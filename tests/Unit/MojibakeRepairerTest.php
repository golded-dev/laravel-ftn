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

it('repairs text without reencoding its frame', function (): void {
    $result = MojibakeRepairer::repair('─── Begrµnsning ───');

    expect($result->text)->toBe('─── Begrænsning ───')
        ->and($result->changed)->toBeTrue();
});

it('preserves correct Danish beside corrupted symbols', function (): void {
    $text = "ikke på 240) ±‗´¯Ý\u{00AD}\u{00AD}▄█Ð·¹³²·¨°. Bag dem ved 200°.";
    $result = MojibakeRepairer::repair($text);

    expect($result->text)->toBe($text)
        ->and($result->changed)->toBeFalse();
});

it('rejects unsafe MIME output while allowing tabs', function (string $text): void {
    $result = MojibakeRepairer::repair($text);

    expect($result->text)->toBe($text)
        ->and($result->changed)->toBeFalse();
})->with([
    '=?ISO-8859-1?Q?abc=01?=',
    '=?ISO-8859-1?Q?abc=7F?=',
    '=?ISO-8859-1?Q?abc=86?=',
    '=?UTF-8?B?77+9?=',
    "\u{0086} =?ISO-8859-1?Q?abc=86?=",
]);

it('allows a decoded MIME tab', function (): void {
    expect(MojibakeRepairer::repair('=?ISO-8859-1?Q?Ada=09S=F8rensen?=')->text)
        ->toBe("Ada\tSørensen");
});

it('repairs damaged words beside correct words', function (): void {
    $result = MojibakeRepairer::repair('Søren skrev om s°getid i går');

    expect($result->text)->toBe('Søren skrev om søgetid i går')
        ->and($result->changed)->toBeTrue();
});

it('recovers mislabelled ASCII MIME without guessing other charsets', function (): void {
    $result = MojibakeRepairer::repair('=?US-ASCII?Q?p=E5?= mandag');

    expect($result->text)->toBe('på mandag')
        ->and($result->changed)->toBeTrue()
        ->and($result->confidence)->toBe(0.95);

    foreach (['=?UTF-8?Q?p=E5?= mandag', '=?UNKNOWN?Q?p=E5?= mandag', '=?US-ASCII?Q?p=86?= mandag'] as $text) {
        expect(MojibakeRepairer::repair($text)->text)->toBe($text);
    }
});

it('leaves uuencoded payload intact even when it resembles MIME', function (): void {
    $text = str_pad('M=?ISO-8859-1?Q?=E5?=', 61, 'A');
    $result = MojibakeRepairer::repair($text);

    expect($result->text)->toBe($text)
        ->and($result->changed)->toBeFalse();
});

it('preserves PGP armour while repairing surrounding text', function (): void {
    $text = "Vi ses pÕ m°det\n-----BEGIN PGP SIGNED MESSAGE-----\nHash: SHA256\n\n"
        ."Vi ses pÕ m°det\n-----BEGIN PGP SIGNATURE-----\n=?ISO-8859-1?Q?=E5?=\n"
        ."-----END PGP SIGNATURE-----\nBruger m°de";
    $expected = "Vi ses på mødet\n-----BEGIN PGP SIGNED MESSAGE-----\nHash: SHA256\n\n"
        ."Vi ses pÕ m°det\n-----BEGIN PGP SIGNATURE-----\n=?ISO-8859-1?Q?=E5?=\n"
        ."-----END PGP SIGNATURE-----\nBruger møde";

    expect(MojibakeRepairer::repair($text)->text)->toBe($expected);
});

it('leaves symbol-only noise unchanged', function (): void {
    $text = "±‗´¯Ý\u{00AD}\u{00AD}Ð·¹³²·¨°";
    $result = MojibakeRepairer::repair($text);

    expect($result->text)->toBe($text)
        ->and($result->changed)->toBeFalse()
        ->and($result->confidence)->toBe(0.0);
});

it('preserves reported art and correct text', function (string $text): void {
    $result = MojibakeRepairer::repair($text);

    expect($result->text)->toBe($text)
        ->and($result->changed)->toBeFalse()
        ->and($result->confidence)->toBe(0.0);
})->with([
    '────────────────────────────────',
    '┌──────────┬──────────┐',
    '█▄▀',
    '─ (Dan) Til/fra Sysop (2:231/116) ───────── SYSOP116 ─',
    '█ -[/] ¯ViL U´CL¯ Õ GRåVeDiGGeR Õ ALCATRAZ [\\]- █',
    '█ Foo▀ GRå █',
    '│ Medlemsmøde på torsdag │',
    'Det var 20 °C i går',
    '10 µm',
]);

it('preserves MIME surrounding text and adjacent encoded words', function (): void {
    expect(MojibakeRepairer::repair('Søren: =?ISO-8859-1?Q?Ada?= =?ISO-8859-1?Q?_S=F8rensen?=')->text)
        ->toBe('Søren: Ada Sørensen');
});

it('recovers UTF-8 displayed using DOS glyphs', function (): void {
    expect(MojibakeRepairer::repair('Bruger m├©de', 'IBMPC')->text)->toBe('Bruger møde');
});

it('decodes base64 MIME and leaves invalid payloads intact', function (): void {
    expect(MojibakeRepairer::repair('=?UTF-8?B?bcO4ZGU=?=')->text)->toBe('møde')
        ->and(MojibakeRepairer::repair('=?UTF-8?B?!!!?=')->text)->toBe('=?UTF-8?B?!!!?=');
});
