<?php

namespace Tests\Feature;

use App\Services\Reports\CompassNarrative;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CompassTraitsTest extends TestCase
{
    private function snapshot(int $directed = 8): array
    {
        $votes = [];
        for ($i = 1; $i <= $directed; $i++) {
            $votes[] = ['id' => $i, 'title' => 'Aanstellen van een speciale gezant nummer '.$i, 'choice' => $i % 3 ? 'agree' : 'disagree', 'topics' => ['Zorg & gezondheid']];
        }
        $votes[] = ['id' => 99, 'title' => 'Water en bodem bij woningbouw', 'choice' => 'neutral', 'topics' => []];
        $dimension = fn ($id, $observed) => ['id' => $id, 'observed_percentage' => $observed, 'percentage' => $observed];

        return ['analysis_context' => ['votes' => $votes], 'compass' => [
            'dimensions' => [$dimension('social', 80), $dimension('progressive', 45), $dimension('international', 25), $dimension('green', null)],
            'spectrum' => $dimension('economic', 70), 'topics' => [['name' => 'Zorg & gezondheid', 'count' => $directed]],
            'evidence' => array_map(fn ($i) => ['issue_id' => $i, 'weights' => ['social' => 1, 'international' => -1, 'economic' => 1]], range(1, $directed)),
        ]];
    }

    private function raw(array $highlights): string
    {
        return json_encode(['summary' => 'Je keuzes laten een duidelijk sociaal patroon zien.', 'highlights' => $highlights]);
    }

    private function trait(string $text, array $ids = [1, 2]): array
    {
        return ['text' => $text, 'evidence_ids' => $ids];
    }

    private function five(): array
    {
        return [
            $this->trait('Je vindt de belangen van mensen belangrijker dan die van organisaties.'),
            $this->trait('Je kiest liever voor bescherming dan voor zo min mogelijk regels.', [2, 3]),
            $this->trait('Je vindt dat Nederland zelf over zijn eigen zaken moet beslissen.', [3, 4]),
            $this->trait('Je wilt dat de overheid actief bijspringt waar dat nodig is.', [4, 5]),
            $this->trait('Je neemt pas een standpunt in als je overtuigd bent.', [99, 6]),
        ];
    }

    public function test_five_value_traits_with_supporting_answers_are_accepted(): void
    {
        $text = app(CompassNarrative::class)->text($this->raw($this->five()), $this->snapshot());
        $this->assertCount(5, $text['highlights']);
        $this->assertSame('compass-poster-v4.2-traits', $text['prompt_version']);
    }

    public function test_traits_must_rest_on_two_answers_once_enough_answers_exist(): void
    {
        $traits = $this->five();
        $traits[0]['evidence_ids'] = [1];
        $this->expectException(\Throwable::class);
        app(CompassNarrative::class)->text($this->raw($traits), $this->snapshot());
    }

    public function test_a_single_answer_is_enough_for_a_small_answer_set(): void
    {
        $snapshot = $this->snapshot(4);
        $this->assertSame(1, app(CompassNarrative::class)->minimumEvidence($snapshot));
        $this->assertSame(1, app(CompassNarrative::class)->schema($snapshot)['properties']['highlights']['items']['properties']['evidence_ids']['minItems']);
    }

    /** @return array<string, array{string}> */
    public static function rejected(): array
    {
        return [
            'repeats a statement' => ['Je steunt het aanstellen van een speciale gezant nummer 1.'],
            'clipped mid sentence' => ['Je kiest voor emissiereductie maar niet voor kleinere sche,?'],
            'does not start with Je' => ['Mensen zijn voor jou belangrijker dan organisaties.'],
            'contains a number' => ['Je steunt 3 voorstellen over zorg en welzijn.'],
        ];
    }

    #[DataProvider('rejected')]
    public function test_traits_that_list_motions_or_are_clipped_are_rejected(string $text): void
    {
        $traits = $this->five();
        $traits[1]['text'] = $text;
        $this->expectException(\Throwable::class);
        app(CompassNarrative::class)->text($this->raw($traits), $this->snapshot());
    }

    public function test_code_fallback_gives_value_statements_with_evidence_instead_of_motion_titles(): void
    {
        $fallback = app(CompassNarrative::class)->fallback($this->snapshot());
        $this->assertSame('code', $fallback['analysis_origin']);
        $this->assertCount(5, $fallback['highlights']);
        $texts = array_column($fallback['highlights'], 'text');
        $this->assertSame('Je vindt dat mensen samen moeten zorgen voor wie het moeilijk heeft.', $texts[0], 'strongest theme first');
        $this->assertContains('Je vindt dat Nederland zelf over zijn eigen zaken moet beslissen.', $texts);
        $this->assertNotContains('Je hecht aan wat bewezen werkt en verandert liever niet te snel.', $texts, 'a theme near the middle is not presented as a trait');
        foreach ($fallback['highlights'] as $h) {
            $this->assertStringStartsWith('Je ', $h['text']);
            $this->assertStringNotContainsString('gezant', $h['text']);
            $this->assertNotEmpty($h['evidence_ids']);
        }
    }
}
