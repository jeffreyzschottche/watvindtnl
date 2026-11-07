<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class CmsMotionSource
{
    // Fixed official origin: CMS input can never turn this into an arbitrary URL fetch.
    public const BASE = 'https://gegevensmagazijn.tweedekamer.nl/OData/v4/2.0';

    public function recent(int $limit): array
    {
        $response = Http::acceptJson()->timeout(12)->connectTimeout(4)
            ->withOptions(['allow_redirects' => false])
            ->get(self::BASE.'/Zaak', [
                '$filter' => "Verwijderd eq false and Soort eq 'Motie' and GestartOp le ".now()->utc()->format('Y-m-d\TH:i:s\Z'),
                '$select' => 'Id,Nummer,Onderwerp,GestartOp',
                '$orderby' => 'GestartOp desc,Nummer desc', '$top' => $limit,
            ]);
        if (! $response->successful() || ! is_array($response->json('value'))) {
            throw ValidationException::withMessages(['source' => ['De officiële Tweede Kamer-bron gaf geen geldige motielijst. Probeer later opnieuw.']]);
        }
        $rows = $response->json('value');
        if (count($rows) > $limit) {
            throw ValidationException::withMessages(['source' => ['De bron overschreed de ingestelde batchgrootte.']]);
        }

        return array_map(function ($row) {
            if (! is_array($row) || ! preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $row['Id'] ?? '') || ! is_string($row['Onderwerp'] ?? null) || ! is_string($row['Nummer'] ?? null) || ! is_string($row['GestartOp'] ?? null)) {
                throw ValidationException::withMessages(['source' => ['Een motie mist de officiële identificatie, titel of datum. Er is niets geïmporteerd.']]);
            }

            return [
                'external_id' => $row['Id'], 'number' => $row['Nummer'], 'title' => trim($row['Onderwerp']),
                'source_date' => substr($row['GestartOp'], 0, 10),
                'source_url' => self::BASE.'/Zaak('.$row['Id'].')',
            ];
        }, $rows);
    }
}
