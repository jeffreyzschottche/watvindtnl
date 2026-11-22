<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CmsMotionSource
{
    // Fixed official origin: CMS input can never turn this into an arbitrary URL fetch.
    public const BASE = 'https://gegevensmagazijn.tweedekamer.nl/OData/v4/2.0';

    public function recent(int $limit, int $offset = 0): array
    {
        $response = Http::acceptJson()->timeout(12)->connectTimeout(4)
            ->withOptions(['allow_redirects' => false])
            ->get(self::BASE.'/Zaak', [
                // Only motions with recorded votes: without them a statement cannot be compared with parties.
                '$filter' => "Verwijderd eq false and Soort eq 'Motie' and GestartOp le ".now()->utc()->format('Y-m-d\TH:i:s\Z')
                    .' and Besluit/any(b: b/Verwijderd eq false and b/Stemming/any(s: s/Verwijderd eq false))',
                '$select' => 'Id,Nummer,Onderwerp,GestartOp',
                '$orderby' => 'GestartOp desc,Nummer desc,Id desc', '$top' => $limit, '$skip' => $offset,
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

    public function details(string $id): array
    {
        abort_unless(Str::isUuid($id), 422, 'Deze motie heeft geen officieel Tweede Kamer-ID.');
        $url = self::BASE.'/Zaak('.$id.')';
        $response = Http::acceptJson()->timeout(8)->connectTimeout(4)
            ->withOptions(['allow_redirects' => false])->get($url, [
                '$select' => 'Id,Soort,Verwijderd,Nummer,Onderwerp,GestartOp',
                '$expand' => 'Besluit($filter=Verwijderd eq false;$select=BesluitTekst;$expand=Stemming($filter=Verwijderd eq false;$select=Soort,ActorNaam,ActorFractie)),Document($filter=Verwijderd eq false and (Soort eq \'Motie\' or Soort eq \'Motie (gewijzigd/nader)\');$select=Id;$orderby=Datum desc;$top=1)',
            ]);
        $zaak = $response->json();
        if (! $response->successful() || ($zaak['Id'] ?? null) !== $id || ($zaak['Soort'] ?? null) !== 'Motie' || ($zaak['Verwijderd'] ?? true) !== false) {
            throw new RuntimeException('De officiële motie is niet beschikbaar.');
        }

        $documentId = data_get($zaak, 'Document.0.Id');
        if (! is_string($documentId) || ! Str::isUuid($documentId)) {
            throw new RuntimeException('Er is nog geen officieel motiedocument beschikbaar.');
        }

        $documentUrl = self::BASE.'/Document('.$documentId.')/resource';

        return ['zaak' => $zaak, 'document_url' => $documentUrl, 'text' => $this->pdfText($documentUrl)];
    }

    private function pdfText(string $url): string
    {
        $path = tempnam(sys_get_temp_dir(), 'wdnl-motion-');
        if ($path === false) {
            throw new RuntimeException('De motie-pdf kon niet worden voorbereid.');
        }
        $maxBytes = 5 * 1024 * 1024;
        try {
            $response = Http::accept('application/pdf')->timeout(8)->connectTimeout(4)->withOptions([
                'allow_redirects' => false,
                'sink' => $path,
                'progress' => function ($total, $downloaded) use ($maxBytes) {
                    if ($total > $maxBytes || $downloaded > $maxBytes) {
                        throw new RuntimeException('De motie-pdf is te groot.');
                    }
                },
            ])->get($url);
            if (! $response->successful() || filesize($path) > $maxBytes) {
                throw new RuntimeException('Het officiële motiedocument kon niet worden gelezen.');
            }
            $result = Process::timeout(5)->run(['pdftotext', '-f', '1', '-l', '10', '-enc', 'UTF-8', $path, '-']);
            $text = trim($result->output());
            if ($result->failed() || $text === '') {
                throw new RuntimeException('Het officiële motiedocument bevat geen leesbare tekst.');
            }

            return mb_substr($text, 0, 20000);
        } finally {
            unlink($path);
        }
    }
}
