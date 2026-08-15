<?php

namespace App\Support;

/**
 * Result types for the Competition Result Engine.
 *
 * - win_loss: vs-format, single winner (OfficialPanel / winner_registration_id).
 * - score:    higher score wins (sorted descending).
 * - time:     time trial / heat, lower time wins (sorted ascending).
 * - ranking:  mass race / finish order, lower number wins (sorted ascending).
 */
final class CompetitionResultType
{
    public const WIN_LOSS = 'win_loss';

    public const SCORE = 'score';

    public const TIME = 'time';

    public const RANKING = 'ranking';

    public const ALL = [
        self::WIN_LOSS,
        self::SCORE,
        self::TIME,
        self::RANKING,
    ];

    public static function isValid(?string $resultType): bool
    {
        return $resultType !== null && in_array($resultType, self::ALL, true);
    }

    /** Result types that can be auto-ranked by sorting. */
    public static function isRanked(string $resultType): bool
    {
        return in_array($resultType, [self::SCORE, self::TIME, self::RANKING], true);
    }

    /**
     * @return 'asc'|'desc'
     */
    public static function sortDirection(string $resultType): string
    {
        return $resultType === self::SCORE ? 'desc' : 'asc';
    }

    public static function label(?string $resultType): string
    {
        return match ($resultType) {
            self::WIN_LOSS => 'Win/Loss',
            self::SCORE => 'Skor (tertinggi menang)',
            self::TIME => 'Waktu (tercepat menang)',
            self::RANKING => 'Ranking (urutan finish)',
            default => 'Ranking',
        };
    }
}
