<?php

namespace App\Support;

use App\Models\PoliticalParty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PoliticalPartyCatalogue
{
    /** Snapshot checked against the Tweede Kamer on 2026-11-07. Historical rows remain separate. */
    public static function seed(): array
    {
        $parties = [
            ['name' => 'Partij voor de Vrijheid', 'abbreviation' => 'PVV', 'website_url' => 'https://www.pvv.nl'],
            ['name' => 'GroenLinks-PvdA', 'abbreviation' => 'GL-PvdA', 'website_url' => 'https://groenlinks-pvda.nl'],
            ['name' => 'Volkspartij voor Vrijheid en Democratie', 'abbreviation' => 'VVD', 'website_url' => 'https://www.vvd.nl'],
            ['name' => 'Nieuw Sociaal Contract', 'abbreviation' => 'NSC', 'website_url' => 'https://partijnieuwsociaalcontract.nl/'],
            ['name' => 'Democraten 66', 'abbreviation' => 'D66', 'website_url' => 'https://www.d66.nl'],
            ['name' => 'BoerBurgerBeweging', 'abbreviation' => 'BBB', 'website_url' => 'https://www.boerburgerbeweging.nl'],
            ['name' => 'Christen-Democratisch Appèl', 'abbreviation' => 'CDA', 'website_url' => 'https://www.cda.nl'],
            ['name' => 'Socialistische Partij', 'abbreviation' => 'SP', 'website_url' => 'https://www.sp.nl'],
            ['name' => 'DENK', 'abbreviation' => 'DENK', 'website_url' => 'https://denk.nl'],
            ['name' => 'Partij voor de Dieren', 'abbreviation' => 'PvdD', 'website_url' => 'https://www.partijvoordedieren.nl'],
            ['name' => 'Forum voor Democratie', 'abbreviation' => 'FvD', 'website_url' => 'https://www.fvd.nl'],
            ['name' => 'Staatkundig Gereformeerde Partij', 'abbreviation' => 'SGP', 'website_url' => 'https://www.sgp.nl'],
            ['name' => 'ChristenUnie', 'abbreviation' => 'CU', 'website_url' => 'https://www.christenunie.nl'],
            ['name' => 'Volt Nederland', 'abbreviation' => 'Volt', 'website_url' => 'https://www.voltnederland.org'],
            ['name' => 'JA21', 'abbreviation' => 'JA21', 'website_url' => 'https://www.ja21.nl'],
            ['name' => 'Progressief Nederland', 'abbreviation' => 'PRO', 'website_url' => null],
            ['name' => 'Groep Markuszower', 'abbreviation' => 'Groep Markuszower', 'website_url' => null],
            ['name' => '50PLUS', 'abbreviation' => '50PLUS', 'website_url' => 'https://50pluspartij.nl'],
            ['name' => 'Lid Keijzer', 'abbreviation' => 'Lid Keijzer', 'website_url' => null],
        ];

        return DB::transaction(function () use ($parties) {
            $created = 0;
            foreach ($parties as $data) {
                $slug = Str::slug($data['abbreviation']);
                $party = PoliticalParty::firstOrCreate(['slug' => $slug], $data + ['logo_url' => null]);
                $created += (int) $party->wasRecentlyCreated;
            }
            $removed = PoliticalParty::whereNotNull('logo_url')->update(['logo_url' => null]);

            return ['created' => $created, 'logos_removed' => $removed, 'total' => PoliticalParty::count(), 'catalogue_date' => '2026-11-07'];
        });
    }
}
