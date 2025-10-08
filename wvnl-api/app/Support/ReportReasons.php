<?php

namespace App\Support;

class ReportReasons
{
    public const INCORRECT_INFORMATION = 'incorrect_information';
    public const OFFENSIVE_INFORMATION = 'offensive_information';
    public const ISSUE_MISFORMULATED = 'issue_misworded';
    public const ARGUMENT_MISFORMULATED = 'argument_misworded';

    /**
     * Alle toegestane meldingsredenen.
     */
    public static function all(): array
    {
        return [
            self::INCORRECT_INFORMATION,
            self::OFFENSIVE_INFORMATION,
            self::ISSUE_MISFORMULATED,
            self::ARGUMENT_MISFORMULATED,
        ];
    }

    /**
     * Normaliseer ruwe data naar een compleet overzicht van meldingen.
     */
    public static function normalize(?array $counts): array
    {
        $normalized = [];

        foreach (self::all() as $reason) {
            $normalized[$reason] = (int) ($counts[$reason] ?? 0);
        }

        return $normalized;
    }
}
