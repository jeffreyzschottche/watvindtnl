<?php

namespace Tests\Feature;

use App\Services\Reports\BalancedScore;
use App\Services\Reports\CompassAnalysis;
use Tests\TestCase;

class BalancedScoresTest extends TestCase
{
    public function test_sparse_unanimous_answers_do_not_imply_a_certain_political_position(): void
    {
        $this->assertSame(63, BalancedScore::value(100, 2));
        $this->assertSame(70, BalancedScore::value(100, 4));
        $this->assertSame(81, BalancedScore::value(100, 10));
        $this->assertSame(37, BalancedScore::value(0, 2));
        $this->assertSame(50, BalancedScore::value(50, 10));
        $this->assertSame(99, BalancedScore::value(100, 1000000));
        $this->assertSame(1, BalancedScore::value(0, 1000000));
    }

    public function test_reversing_every_choice_produces_complementary_scores_at_every_sample_size(): void
    {
        foreach ([2, 3, 4, 5, 10, 50, 1000] as $count) {
            for ($observed = 0; $observed <= 100; $observed++) {
                $this->assertSame(100, BalancedScore::value($observed, $count) + BalancedScore::value(100 - $observed, $count));
            }
        }
    }

    private function analysis(array $weights, array $choices): array
    {
        $entries = $meta = [];
        foreach ($weights as $index => $weight) {
            $id = $index + 1;
            $entries[] = ['id' => $id, 'choice' => $choices[$index]];
            $meta[$id] = (object) ['motion_id' => null, 'verified_at' => now(), 'source_date' => now()->toDateString(), 'compass_weights' => ['economic' => $weight]];
        }

        return app(CompassAnalysis::class)->build($entries, $meta);
    }

    public function test_weights_and_neutral_answers_do_not_masquerade_as_extra_evidence(): void
    {
        $light = $this->analysis([1, 1], ['agree', 'agree']);
        $heavy = $this->analysis([2, 2], ['agree', 'agree']);
        $neutral = $this->analysis([1, 1, 2], ['agree', 'agree', 'neutral']);
        foreach ([$light, $heavy, $neutral] as $c) {
            $this->assertSame(63, $c['spectrum']['percentage']);
            $this->assertEquals(100, $c['spectrum']['observed_percentage']);
            $this->assertSame(2, $c['spectrum']['compared']);
            $this->assertNull($c['spectrum']['sensitivity']);
            $this->assertSame(6, $c['score_adjustment']['neutral_anchor']);
        }
        $this->assertNull($this->analysis([0, 0], ['agree', 'agree'])['spectrum']['percentage']);
        $this->assertNull($this->analysis([1, 1], ['neutral', 'neutral'])['spectrum']['percentage']);
        $this->assertNull($this->analysis([1], ['agree'])['spectrum']['percentage']);
    }

    public function test_more_distinct_support_strengthens_direction_and_leave_one_out_is_adjusted_too(): void
    {
        $c = $this->analysis([1, 1, 1, 1], ['agree', 'agree', 'agree', 'agree']);
        $this->assertSame(70, $c['spectrum']['percentage']);
        $this->assertSame(['min' => 67, 'max' => 67], $c['spectrum']['sensitivity']);
        $this->assertNull($c['dimensions'][0]['percentage']);
    }
}
