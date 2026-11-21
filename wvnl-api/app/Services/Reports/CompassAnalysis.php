<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;

/** Fixed editorial mappings; provisional indications never invent missing dimensions or party positions. */
class CompassAnalysis
{
    public const VERSION = 'compass-v4.2';

    /** Saved one-page poster reports keep rendering with the method they were made with. */
    public const POSTER_VERSIONS = ['compass-v4', 'compass-v4.1', self::VERSION];

    public const MINIMUM = 5;

    public const EARLY_MINIMUM = 2;

    public const DIMENSIONS = [
        'social' => ['left' => 'Sociaal', 'right' => 'Individueel', 'icon' => 'people'],
        'progressive' => ['left' => 'Progressief', 'right' => 'Behoudend', 'icon' => 'spark'],
        'international' => ['left' => 'Internationaal', 'right' => 'Nationaal', 'icon' => 'globe'],
        'green' => ['left' => 'Groen', 'right' => 'Economische groei', 'icon' => 'leaf'],
        'economic' => ['left' => 'Links', 'right' => 'Rechts', 'icon' => 'balance'],
    ];

    public const TYPES = CompassTypes::ALL;

    public function build(array $entries, array $metadata): array
    {
        $groups = [];
        $contexts = [];
        foreach ($entries as $entry) {
            $m = $metadata[$entry['id']] ?? null;
            $context = $m?->compass_context ?? null;
            $context = is_string($context) ? json_decode($context, true) : $context;
            $context = is_array($context) ? $context : [];
            if (isset($context['text_hash']) && ! hash_equals($context['text_hash'], $entry['content_hash'] ?? '')) {
                $context['_outdated'] = true;
            }
            $contexts[$entry['id']] = $context;
            $key = $m?->motion_id ? 'motion:'.$m->motion_id : (! empty($context['canonical_key']) ? 'content:'.$context['canonical_key'] : 'issue:'.$entry['id']);
            $groups[$key][] = $entry;
        }
        $normal = [];
        $duplicates = 0;
        $conflicting = 0;
        foreach ($groups as $key => $group) {
            $duplicates += count($group) - 1;
            if (count(array_unique(array_column($group, 'choice'))) > 1) {
                $conflicting++;

                continue;
            }$normal[$key] = $group[0];
        }
        $scores = [];
        $evidence = [];
        $used = [];
        $historicalUsed = [];
        $topics = [];
        foreach ($normal as $entry) {
            $context = $contexts[$entry['id']];
            if ($context['_outdated'] ?? false) {
                continue;
            }
            foreach ($context['topics'] ?? [] as $topic) {
                $topics[$topic] ??= ['name' => $topic, 'count' => 0, 'agree' => 0, 'disagree' => 0, 'neutral' => 0];
                $topics[$topic]['count']++;
                $topics[$topic][$entry['choice']]++;
            }
        }
        foreach (self::DIMENSIONS as $key => $labels) {
            $sum = 0;
            $weight = 0;
            $count = 0;
            $neutral = 0;
            $historical = 0;
            $contributions = [];
            foreach ($normal as $source => $entry) {
                $m = $metadata[$entry['id']] ?? null;
                $review = $m?->compass_reviewed_at ?? $m?->verified_at;
                if (! $m || ! $review) {
                    continue;
                }$context = $contexts[$entry['id']];
                if (($context['_outdated'] ?? false) || isset($context['text_hash']) && ! hash_equals($context['text_hash'], $entry['content_hash'] ?? '')) {
                    continue;
                }$historic = ($context['historical'] ?? false) === true;
                $date = $m->source_date ?: $review;
                // Weights describe the wording the user answered, so their answer is a current opinion whatever the motion's age.
                $wording = ($context['basis'] ?? null) === 'statement_wording';
                if (! $wording && ! $historic && Carbon::parse($date)->lt(now()->subDays(730))) {
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
                $v = $w * ($entry['choice'] === 'agree' ? 1 : -1);
                $sum += $v;
                $weight += abs($w);
                $count++;
                $historical += (int) $historic;
                $contributions[] = [$v, abs($w)];
                $used[$source] = true;
                if ($historic) {
                    $historicalUsed[$source] = true;
                }
                $evidence[$source] = ['issue_id' => $entry['id'], 'canonical_key' => $source, 'motion_id' => $m->motion_id, 'weights' => $mapping, 'reviewed_at' => $review, 'source_date' => $m->source_date, 'historical' => $historic, 'rationale' => $context['rationale'] ?? null];
            }
            $observed = $count >= self::EARLY_MINIMUM ? 50 * (1 + $sum / $weight) : null;
            $percentage = $observed === null ? null : BalancedScore::value($observed, $count);
            $sensitivity = [];
            if ($percentage !== null) {
                foreach ($contributions as [$v,$w]) {
                    if ($count - 1 >= self::EARLY_MINIMUM && $weight > $w) {
                        $sensitivity[] = BalancedScore::value(50 * (1 + ($sum - $v) / ($weight - $w)), $count - 1);
                    }
                }
            }
            $scores[$key] = $labels + ['id' => $key, 'observed_percentage' => $observed, 'percentage' => $percentage, 'opposite_percentage' => $percentage === null ? null : 100 - $percentage, 'compared' => $count, 'neutral' => $neutral, 'historical' => $historical, 'minimum' => self::MINIMUM, 'early_minimum' => self::EARLY_MINIMUM, 'provisional' => $percentage !== null && $count < self::MINIMUM, 'sensitivity' => $sensitivity ? ['min' => min($sensitivity), 'max' => max($sensitivity)] : null];
        }
        $available = array_filter(array_slice($scores, 0, 4), fn ($d) => $d['percentage'] !== null);
        $complete = count($available) === 4 && count($used) >= 10 && count(array_filter($available, fn ($d) => $d['provisional'])) === 0;
        $type = null;
        if (count($available) >= 2 && count($used) >= 8) {
            $best = INF;
            foreach (self::TYPES as $id => $candidate) {
                $eligible = true;
                foreach ($candidate['requires'] as $key => [$min, $max]) {
                    $value = $scores[$key]['observed_percentage'];
                    if ($value === null || $value < $min || $value > $max) {
                        $eligible = false;
                        break;
                    }
                }
                if (! $eligible) {
                    continue;
                }
                $distance = 0;
                foreach ($available as $dimension) {
                    $index = array_search($dimension['id'], array_keys(self::DIMENSIONS), true);
                    // Compare both the observed direction and type point on the same tempered scale.
                    // Sparse coverage reduces an axis's influence; it does not prove centrism.
                    $distance += (($dimension['observed_percentage'] - $candidate['point'][$index]) * BalancedScore::strength($dimension['compared'])) ** 2;
                }
                if ($distance < $best) {
                    $best = $distance;
                    $type = ['id' => $id, 'provisional' => ! $complete, 'based_on_dimensions' => array_column($available, 'id')] + array_diff_key($candidate, ['point' => true, 'requires' => true]);
                }
            }
        }
        if ($type) {
            $strongest = array_values($available);
            usort($strongest, fn ($a, $b) => (abs($b['percentage'] - 50) <=> abs($a['percentage'] - 50)) ?: (array_search($a['id'], array_keys(self::DIMENSIONS), true) <=> array_search($b['id'], array_keys(self::DIMENSIONS), true)));
            $type['traits'] = array_map(fn ($d) => match ($d['id']) {
                'social' => $d['observed_percentage'] >= 60 ? 'Sociale voorzieningen' : ($d['observed_percentage'] <= 40 ? 'Individuele ruimte' : 'Afgewogen sociale keuzes'),
                'progressive' => $d['observed_percentage'] >= 60 ? 'Gericht op vernieuwing' : ($d['observed_percentage'] <= 40 ? 'Gericht op behoud' : 'Behoud en vernieuwing'),
                'international' => $d['observed_percentage'] >= 60 ? 'Internationale samenwerking' : ($d['observed_percentage'] <= 40 ? 'Nationale besluitvorming' : 'Gemengde internationale keuzes'),
                'green' => $d['observed_percentage'] >= 60 ? 'Duurzaamheid' : ($d['observed_percentage'] <= 40 ? 'Ruimte voor economische groei' : 'Groen en groei afgewogen'),
            }, array_slice($strongest, 0, 3));
        }
        usort($topics, fn ($a, $b) => ($b['count'] <=> $a['count']) ?: strcmp($a['name'], $b['name']));

        return ['version' => self::VERSION, 'score_adjustment' => BalancedScore::metadata(), 'type_catalogue' => CompassTypes::catalogue(), 'type' => $type, 'dimensions' => array_values(array_slice($scores, 0, 4)), 'spectrum' => $scores['economic'], 'used_votes' => count($used), 'historical_votes' => count($historicalUsed), 'unique_answers' => count($groups), 'duplicate_answers' => $duplicates, 'conflicting_groups' => $conflicting, 'topics' => array_slice(array_values($topics), 0, 5), 'minimum_per_dimension' => self::MINIMUM, 'early_minimum_per_dimension' => self::EARLY_MINIMUM, 'evidence' => array_values($evidence), 'status' => $type ? ($complete ? 'ready' : 'provisional') : 'insufficient_data',
            'highlights' => array_values(array_map(fn ($d) => $d['observed_percentage'] >= 60 ? match ($d['id']) {
                'social' => 'Je antwoorden leggen vaker de nadruk op gezamenlijke voorzieningen en sociale betrokkenheid.','progressive' => 'Je antwoorden leggen vaker de nadruk op vernieuwing en maatschappelijke verandering.','international' => 'Je antwoorden leggen vaker de nadruk op internationale samenwerking.','green' => 'Je antwoorden leggen vaker de nadruk op milieu en duurzaamheid.'
            } : ($d['observed_percentage'] <= 40 ? match ($d['id']) {
                'social' => 'Je antwoorden leggen vaker de nadruk op individuele verantwoordelijkheid en keuzevrijheid.','progressive' => 'Je antwoorden leggen vaker de nadruk op behoud en bestaande tradities.','international' => 'Je antwoorden leggen vaker de nadruk op nationale besluitvorming.','green' => 'Je antwoorden leggen vaker de nadruk op economische groei in deze afwegingen.'
            } : 'Je antwoorden combineren beide kanten van de afweging '.mb_strtolower($d['left']).' / '.mb_strtolower($d['right']).'.'), $available))];
    }
}
