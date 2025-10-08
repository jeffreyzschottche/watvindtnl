<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PoliticalPartiesSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $parties = [
            ['name' => 'Partij voor de Vrijheid', 'abbreviation' => 'PVV', 'website_url' => 'https://www.pvv.nl'],
            ['name' => 'GroenLinks-PvdA', 'abbreviation' => 'GL-PvdA', 'website_url' => 'https://groenlinks-pvda.nl'],
            ['name' => 'Volkspartij voor Vrijheid en Democratie', 'abbreviation' => 'VVD', 'website_url' => 'https://www.vvd.nl'],
            ['name' => 'Nieuw Sociaal Contract', 'abbreviation' => 'NSC', 'website_url' => 'https://www.nsc.nl'],
            ['name' => 'Democraten 66', 'abbreviation' => 'D66', 'website_url' => 'https://www.d66.nl'],
            ['name' => 'BoerBurgerBeweging', 'abbreviation' => 'BBB', 'website_url' => 'https://www.boerburgerbeweging.nl'],
            ['name' => 'Christen-Democratisch Appèl', 'abbreviation' => 'CDA', 'website_url' => 'https://www.cda.nl'],
            ['name' => 'Socialistische Partij', 'abbreviation' => 'SP', 'website_url' => 'https://www.sp.nl'],
            ['name' => 'DENK', 'abbreviation' => 'DENK', 'website_url' => 'https://www.bewegingdenk.nl'],
            ['name' => 'Partij voor de Dieren', 'abbreviation' => 'PvdD', 'website_url' => 'https://www.partijvoordedieren.nl'],
            ['name' => 'Forum voor Democratie', 'abbreviation' => 'FvD', 'website_url' => 'https://www.fvd.nl'],
            ['name' => 'Staatkundig Gereformeerde Partij', 'abbreviation' => 'SGP', 'website_url' => 'https://www.sgp.nl'],
            ['name' => 'ChristenUnie', 'abbreviation' => 'CU', 'website_url' => 'https://www.christenunie.nl'],
            ['name' => 'Volt Nederland', 'abbreviation' => 'Volt', 'website_url' => 'https://www.voltnederland.org'],
            ['name' => 'JA21', 'abbreviation' => 'JA21', 'website_url' => 'https://www.ja21.nl'],
        ];

        foreach ($parties as $p) {
            $slug = Str::slug($p['abbreviation'] ?: $p['name']);

            DB::table('political_parties')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $p['name'],
                    'abbreviation' => $p['abbreviation'],
                    'logo_url' => null,
                    'website_url' => $p['website_url'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}

