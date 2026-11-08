<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;

/** Versioned editorial scoring. The LLM never chooses a score, label or party. */
class CompassAnalysis
{
    public const VERSION = 'compass-v2.0';

    public const MINIMUM = 5;

    public const DIMENSIONS = [
        'social' => ['left' => 'Sociaal', 'right' => 'Individueel', 'icon' => 'people'],
        'progressive' => ['left' => 'Progressief', 'right' => 'Behoudend', 'icon' => 'spark'],
        'international' => ['left' => 'Internationaal', 'right' => 'Nationaal', 'icon' => 'globe'],
        'green' => ['left' => 'Groen', 'right' => 'Economische groei', 'icon' => 'leaf'],
        'economic' => ['left' => 'Links', 'right' => 'Rechts', 'icon' => 'balance'],
    ];

    public const TYPES = [
        'sociale-vernieuwer' => ['name' => 'De Sociale Vernieuwer', 'icon' => 'people', 'traits' => ['Sociaal betrokken', 'Toekomstgericht', 'Gericht op duurzaamheid'], 'point' => [75, 75, 65, 65]],
        'pragmaticus' => ['name' => 'De Pragmaticus', 'icon' => 'balance', 'traits' => ['Zoekt het midden', 'Weegt belangen af', 'Combineert perspectieven'], 'point' => [50, 50, 50, 50]],
        'beschermer' => ['name' => 'De Beschermer', 'icon' => 'shield', 'traits' => ['Hecht aan zekerheid', 'Gericht op continuïteit', 'Sociaal betrokken'], 'point' => [75, 25, 35, 40]],
        'traditionele-realist' => ['name' => 'De Traditionele Realist', 'icon' => 'building', 'traits' => ['Hecht aan traditie', 'Nationale oriëntatie', 'Individuele ruimte'], 'point' => [30, 20, 25, 25]],
        'ondernemer' => ['name' => 'De Ondernemer', 'icon' => 'chart', 'traits' => ['Individuele ruimte', 'Gericht op groei', 'Internationale blik'], 'point' => [20, 50, 65, 20]],
        'vrijdenker' => ['name' => 'De Vrijdenker', 'icon' => 'spark', 'traits' => ['Gericht op vernieuwing', 'Individuele ruimte', 'Open internationale blik'], 'point' => [25, 85, 75, 50]],
        'nationale-verbinder' => ['name' => 'De Nationale Verbinder', 'icon' => 'people', 'traits' => ['Sociaal betrokken', 'Nationale oriëntatie', 'Zoekt samenhang'], 'point' => [80, 50, 15, 50]],
        'groene-voorloper' => ['name' => 'De Groene Voorloper', 'icon' => 'leaf', 'traits' => ['Duurzaamheid voorop', 'Gericht op vernieuwing', 'Internationale blik'], 'point' => [55, 75, 65, 95]],
        'idealist' => ['name' => 'De Idealist', 'icon' => 'flag', 'traits' => ['Gelijkheid voorop', 'Gericht op vernieuwing', 'Internationale solidariteit'], 'point' => [95, 95, 90, 70]],
    ];

    public function build(array $entries, array $metadata): array
    {
        $scores = [];
        $evidence = [];
        $used = [];
        foreach (self::DIMENSIONS as $key => $labels) {
            $sum = 0;
            $weight = 0;
            $count = 0;
            $neutral = 0;
            $seen = [];
            $contributions = [];
            foreach ($entries as $entry) {
                $m = $metadata[$entry['id']] ?? null;
                if (! $m || ! $m->verified_at) {
                    continue;
                }
                $date = $m->source_date ?: $m->verified_at;
                if (Carbon::parse($date)->lt(now()->subDays(730))) {
                    continue;
                }
                $mapping = is_string($m->compass_weights) ? json_decode($m->compass_weights, true) : $m->compass_weights;
                $w = $mapping[$key] ?? 0;
                if (! is_int($w) || ! in_array($w, [-2, -1, 1, 2], true)) {
                    continue;
                }
                if ($entry['choice'] === 'neutral') {
                    $neutral++;

                    continue;
                }
                $source = $m->motion_id ? 'motion:'.$m->motion_id : 'issue:'.$entry['id'];
                if (isset($seen[$source])) {
                    continue;
                }
                $seen[$source] = true;
                $v = $w * ($entry['choice'] === 'agree' ? 1 : -1);
                $sum += $v;
                $weight += abs($w);
                $count++;
                $contributions[] = [$v, abs($w)];
                $used[$source] = true;
                $evidence[$entry['id']] = ['issue_id' => $entry['id'], 'motion_id' => $m->motion_id, 'weights' => $mapping, 'reviewed_at' => $m->verified_at, 'source_date' => $m->source_date];
            }
            $percentage = $count >= self::MINIMUM ? (int) round(50 * (1 + $sum / $weight)) : null;
            // Leave-one-out sensitivity, not a statistical confidence interval.
            $sensitivity = [];
            if ($percentage !== null) {
                foreach ($contributions as [$v, $w]) {
                    if ($weight > $w) {
                        $sensitivity[] = (int) round(50 * (1 + ($sum - $v) / ($weight - $w)));
                    }
                }
            }
            $scores[$key] = $labels + ['id' => $key, 'percentage' => $percentage, 'opposite_percentage' => $percentage === null ? null : 100 - $percentage, 'compared' => $count, 'neutral' => $neutral, 'minimum' => self::MINIMUM,
                'sensitivity' => $sensitivity ? ['min' => min($sensitivity), 'max' => max($sensitivity)] : null];
        }
        $type = null;
        if (count($used) >= 10 && count(array_filter(array_slice($scores, 0, 4), fn ($d) => $d['percentage'] !== null)) === 4) {
            $point = array_column(array_slice($scores, 0, 4), 'percentage');
            $best = INF;
            foreach (self::TYPES as $id => $candidate) {
                $distance = array_sum(array_map(fn ($a, $b) => ($a - $b) ** 2, $point, $candidate['point']));
                if ($distance < $best) {
                    $best = $distance;
                    $type = ['id' => $id] + array_diff_key($candidate, ['point' => true]);
                }
            }
        }

        return ['version' => self::VERSION, 'type' => $type, 'dimensions' => array_values(array_slice($scores, 0, 4)), 'spectrum' => $scores['economic'], 'used_votes' => count($used), 'minimum_per_dimension' => self::MINIMUM,
            'evidence' => array_values($evidence), 'status' => $type ? 'ready' : 'insufficient_data',
            'highlights' => array_values(array_map(fn ($d) => $d['percentage'] >= 60 ? match ($d['id']) {
                'social' => 'Je antwoorden leggen vaker de nadruk op gezamenlijke voorzieningen en sociale betrokkenheid.', 'progressive' => 'Je antwoorden leggen vaker de nadruk op vernieuwing en maatschappelijke verandering.', 'international' => 'Je antwoorden leggen vaker de nadruk op internationale samenwerking.', 'green' => 'Je antwoorden leggen vaker de nadruk op milieu en duurzaamheid.'
            } : ($d['percentage'] <= 40 ? match ($d['id']) {
                'social' => 'Je antwoorden leggen vaker de nadruk op individuele verantwoordelijkheid en keuzevrijheid.', 'progressive' => 'Je antwoorden leggen vaker de nadruk op behoud en bestaande tradities.', 'international' => 'Je antwoorden leggen vaker de nadruk op nationale besluitvorming.', 'green' => 'Je antwoorden leggen vaker de nadruk op economische groei in deze afwegingen.'
            } : 'Je antwoorden combineren beide kanten van de afweging '.mb_strtolower($d['left']).' / '.mb_strtolower($d['right']).'.'), array_filter(array_slice($scores, 0, 4), fn ($d) => $d['percentage'] !== null)))];
    }
}
