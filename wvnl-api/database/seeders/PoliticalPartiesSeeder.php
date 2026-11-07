<?php

namespace Database\Seeders;

use App\Support\PoliticalPartyCatalogue;
use Illuminate\Database\Seeder;

class PoliticalPartiesSeeder extends Seeder
{
    public function run(): void
    {
        $result = PoliticalPartyCatalogue::seed();
        $this->command?->info("Partijen: {$result['created']} toegevoegd, {$result['logos_removed']} logoverwijzingen verwijderd. Bestaande IDs en historische partijen behouden.");
    }
}
