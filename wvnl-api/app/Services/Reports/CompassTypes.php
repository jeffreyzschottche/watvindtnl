<?php

namespace App\Services\Reports;

/** Presentation archetypes, defined by transparent editorial rules; never psychological diagnoses. */
class CompassTypes
{
    public const ALL = [
        'sociale-vernieuwer' => ['name' => 'De Sociale Vernieuwer', 'icon' => 'people', 'color' => '#008f87', 'point' => [80, 80, 65, 65], 'requires' => ['social' => [60, 100], 'progressive' => [60, 100]], 'description' => 'Je combineert steun voor gezamenlijke voorzieningen met maatschappelijke vernieuwing.'],
        'groene-vernieuwer' => ['name' => 'De Groene Vernieuwer', 'icon' => 'leaf', 'color' => '#237b55', 'point' => [55, 85, 65, 90], 'requires' => ['progressive' => [60, 100], 'green' => [65, 100]], 'description' => 'Je combineert maatschappelijke vernieuwing met een duidelijke nadruk op duurzaamheid.'],
        'internationale-verbinder' => ['name' => 'De Internationale Verbinder', 'icon' => 'globe', 'color' => '#087d92', 'point' => [75, 50, 90, 50], 'requires' => ['social' => [50, 100], 'international' => [65, 100]], 'description' => 'Je combineert gezamenlijke voorzieningen met samenwerking over landsgrenzen.'],
        'praktische-hervormer' => ['name' => 'De Praktische Hervormer', 'icon' => 'balance', 'color' => '#4969a8', 'point' => [50, 70, 45, 50], 'requires' => ['progressive' => [60, 100]], 'description' => 'Je antwoorden sluiten aan bij vernieuwing, met gemengde keuzes op andere thema’s.'],
        'sociale-beschermer' => ['name' => 'De Sociale Beschermer', 'icon' => 'shield', 'color' => '#246783', 'point' => [85, 30, 35, 50], 'requires' => ['social' => [65, 100], 'progressive' => [0, 40]], 'description' => 'Je combineert sociale voorzieningen met behoud van bestaande maatschappelijke structuren.'],
        'zorgzame-behouder' => ['name' => 'De Zorgzame Behouder', 'icon' => 'people', 'color' => '#7f5884', 'point' => [70, 15, 60, 60], 'requires' => ['social' => [55, 100], 'progressive' => [0, 30]], 'description' => 'Je antwoorden verbinden gezamenlijke zorg en zekerheid met terughoudendheid bij verandering.'],
        'lokale-verbinder' => ['name' => 'De Lokale Verbinder', 'icon' => 'people', 'color' => '#587b3a', 'point' => [70, 55, 25, 70], 'requires' => ['social' => [50, 100], 'international' => [0, 40]], 'description' => 'Je combineert gezamenlijke voorzieningen met een nadruk op besluitvorming dichter bij huis.'],
        'nationale-beschermer' => ['name' => 'De Nationale Beschermer', 'icon' => 'shield', 'color' => '#526489', 'point' => [60, 25, 10, 35], 'requires' => ['international' => [0, 30], 'progressive' => [0, 45]], 'description' => 'Je antwoorden leggen nadruk op nationale besluitvorming en het behouden van bestaande structuren.'],
        'vrije-denker' => ['name' => 'De Vrije Denker', 'icon' => 'spark', 'color' => '#7156a1', 'point' => [25, 90, 70, 55], 'requires' => ['social' => [0, 45], 'progressive' => [65, 100]], 'description' => 'Je combineert individuele ruimte met steun voor maatschappelijke vernieuwing.'],
        'vrijheidsbewaker' => ['name' => 'De Vrijheidsbewaker', 'icon' => 'flag', 'color' => '#90623d', 'point' => [10, 55, 40, 35], 'requires' => ['social' => [0, 35]], 'description' => 'Je antwoorden leggen in deze afwegingen relatief veel nadruk op individuele ruimte.'],
        'ondernemende-vernieuwer' => ['name' => 'De Ondernemende Vernieuwer', 'icon' => 'chart', 'color' => '#a85e36', 'point' => [20, 75, 55, 15], 'requires' => ['social' => [0, 40], 'progressive' => [60, 100], 'green' => [0, 40]], 'description' => 'Je combineert individuele ruimte en vernieuwing met ruimte voor economische groei.'],
        'zelfstandige-bouwer' => ['name' => 'De Zelfstandige Bouwer', 'icon' => 'building', 'color' => '#78683b', 'point' => [25, 40, 30, 30], 'requires' => ['social' => [0, 45], 'international' => [0, 50]], 'description' => 'Je combineert individuele verantwoordelijkheid met een nadruk op nationale besluitvorming.'],
        'groene-pragmaticus' => ['name' => 'De Groene Pragmaticus', 'icon' => 'leaf', 'color' => '#4b824c', 'point' => [50, 50, 55, 85], 'requires' => ['green' => [65, 100]], 'description' => 'Je legt nadruk op duurzaamheid, met gemengde keuzes op andere beschikbare thema’s.'],
        'traditionele-bouwer' => ['name' => 'De Traditionele Bouwer', 'icon' => 'building', 'color' => '#856174', 'point' => [40, 10, 30, 45], 'requires' => ['progressive' => [0, 30]], 'description' => 'Je antwoorden sluiten aan bij behoud en continuïteit van bestaande maatschappelijke structuren.'],
        'internationale-ondernemer' => ['name' => 'De Internationale Ondernemer', 'icon' => 'globe', 'color' => '#347e8b', 'point' => [15, 55, 90, 20], 'requires' => ['social' => [0, 40], 'international' => [65, 100], 'green' => [0, 40]], 'description' => 'Je combineert individuele ruimte en economische groei met internationale samenwerking.'],
        'betrokken-middenkoers' => ['name' => 'De Betrokken Middenkoers', 'icon' => 'balance', 'color' => '#677686', 'point' => [50, 50, 50, 50], 'requires' => ['social' => [30, 70], 'progressive' => [30, 70], 'international' => [30, 70], 'green' => [30, 70]], 'description' => 'Je antwoorden combineren op alle vier de thema’s elementen van beide kanten van de afweging.'],
    ];

    public static function catalogue(): array
    {
        $rows = [];
        foreach (self::ALL as $id => $type) {
            $rows[] = ['id' => $id] + array_intersect_key($type, array_flip(['name', 'icon', 'color', 'description']));
        }

        return $rows;
    }
}
