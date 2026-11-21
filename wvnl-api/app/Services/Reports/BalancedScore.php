<?php

namespace App\Services\Reports;

/** Transparent product regularisation, not a probability or confidence interval. */
final class BalancedScore
{
    public const ANCHOR = 6;

    public const VERSION = 'neutral-anchor-v1';

    public static function strength(int $answers): float
    {
        return $answers / ($answers + self::ANCHOR);
    }

    public static function value(float $observed, int $answers): int
    {
        // Symmetric rounding preserves complementary scores when every answer reverses.
        // Reserve the endpoints: finite evidence never means absolute certainty.
        $deviation = min(49, (int) round(abs($observed - 50) * self::strength($answers)));

        return 50 + ($observed < 50 ? -$deviation : $deviation);
    }

    public static function metadata(): array
    {
        return ['version' => self::VERSION, 'neutral_anchor' => self::ANCHOR, 'display_range' => [1, 99]];
    }
}
