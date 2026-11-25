<?php

namespace App\Holidays;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Holidays from ArgentinaDatos (https://argentinadatos.com), a free public
 * API: GET /v1/feriados/{year} returns [{fecha, tipo, nombre}, ...].
 * Verified on 2026-11-25.
 */
class ArgentinaDatosProvider implements HolidayProvider
{
    public function __construct(
        private string $baseUrl = 'https://api.argentinadatos.com/v1',
    ) {}

    public function forYear(int $year): array
    {
        try {
            $response = Http::acceptJson()
                ->timeout(15)
                ->retry(2, 500, throw: false)
                ->get("{$this->baseUrl}/feriados/{$year}");
        } catch (ConnectionException $e) {
            throw new HolidayProviderException("Could not reach ArgentinaDatos: {$e->getMessage()}", previous: $e);
        }

        if (! $response->successful() || ! is_array($response->json())) {
            throw new HolidayProviderException("ArgentinaDatos answered {$response->status()} for {$year}.");
        }

        return collect($response->json())
            ->filter(fn ($item) => is_array($item) && isset($item['fecha'], $item['nombre']))
            ->map(fn (array $item) => [
                'date' => substr((string) $item['fecha'], 0, 10),
                'name' => (string) $item['nombre'],
                'type' => isset($item['tipo']) ? (string) $item['tipo'] : null,
            ])
            ->values()
            ->all();
    }
}
