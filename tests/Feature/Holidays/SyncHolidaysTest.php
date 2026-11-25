<?php

use App\Models\Holiday;
use Illuminate\Support\Facades\Http;

test('holidays of the year and the next one are copied locally', function () {
    Http::fake([
        '*/feriados/2026' => Http::response([
            ['fecha' => '2026-11-23', 'tipo' => 'trasladable', 'nombre' => 'Día de la Soberanía Nacional'],
            ['fecha' => '2026-12-08', 'tipo' => 'inamovible', 'nombre' => 'Inmaculada Concepción de María'],
        ]),
        '*/feriados/2027' => Http::response([
            ['fecha' => '2027-01-01', 'tipo' => 'inamovible', 'nombre' => 'Año nuevo'],
        ]),
    ]);

    $this->artisan('holidays:sync', ['year' => 2026])->assertSuccessful();

    expect(Holiday::count())->toBe(3)
        ->and(Holiday::where('date', '2026-11-23')->value('name'))->toBe('Día de la Soberanía Nacional');
});

test('syncing again updates instead of duplicating', function () {
    Holiday::create(['date' => '2026-11-23', 'name' => 'Viejo nombre', 'type' => null]);

    Http::fake([
        '*/feriados/2026' => Http::response([
            ['fecha' => '2026-11-23', 'tipo' => 'trasladable', 'nombre' => 'Día de la Soberanía Nacional'],
        ]),
        '*/feriados/2027' => Http::response([]),
    ]);

    $this->artisan('holidays:sync', ['year' => 2026])->assertSuccessful();

    expect(Holiday::count())->toBe(1)
        ->and(Holiday::sole()->name)->toBe('Día de la Soberanía Nacional')
        ->and(Holiday::sole()->type)->toBe('trasladable');
});

test('if the API fails the existing holidays are kept', function () {
    Holiday::create(['date' => '2026-11-23', 'name' => 'Día de la Soberanía Nacional']);

    Http::fake(['*' => Http::response('Service Unavailable', 503)]);

    $this->artisan('holidays:sync', ['year' => 2026])->assertFailed();

    expect(Holiday::count())->toBe(1);
});
