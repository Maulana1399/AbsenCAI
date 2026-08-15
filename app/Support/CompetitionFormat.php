<?php

namespace App\Support;

/**
 * Competition formats (5 agreed formats).
 *
 * These are string constants stored on `competition_classes.format`. The match
 * engine and team formation logic branch on these.
 */
final class CompetitionFormat
{
    public const INDIVIDUAL_HEAT = 'individual_heat';

    public const INDIVIDUAL_MASS = 'individual_mass';

    public const TEAM_VS_TEAM = 'team_vs_team';

    public const TEAM_MASS = 'team_mass';

    public const INDIVIDUAL_VS_INDIVIDUAL = 'individual_vs_individual';

    public const ALL = [
        self::INDIVIDUAL_HEAT,
        self::INDIVIDUAL_MASS,
        self::TEAM_VS_TEAM,
        self::TEAM_MASS,
        self::INDIVIDUAL_VS_INDIVIDUAL,
    ];

    /** Team-based formats require a Team per Kelompok. */
    public const TEAM_FORMATS = [
        self::TEAM_VS_TEAM,
        self::TEAM_MASS,
    ];

    /** Head-to-head formats may use a bracket. */
    public const VS_FORMATS = [
        self::TEAM_VS_TEAM,
        self::INDIVIDUAL_VS_INDIVIDUAL,
    ];

    /** Mass formats use a single heat + ranking (no bracket). */
    public const MASS_FORMATS = [
        self::INDIVIDUAL_MASS,
        self::TEAM_MASS,
    ];

    public static function isValid(string $format): bool
    {
        return in_array($format, self::ALL, true);
    }

    public static function isTeamFormat(?string $format): bool
    {
        return in_array($format, self::TEAM_FORMATS, true);
    }

    public static function isVsFormat(?string $format): bool
    {
        return in_array($format, self::VS_FORMATS, true);
    }

    public static function isMass(?string $format): bool
    {
        return in_array($format, self::MASS_FORMATS, true);
    }

    public static function requiresBracket(?string $format): bool
    {
        return self::isVsFormat($format);
    }

    /**
     * Default result type for a format.
     *
     * @return 'win_loss'|'score'|'time'|'ranking'
     */
    public static function defaultResultType(?string $format): string
    {
        return match ($format) {
            self::INDIVIDUAL_HEAT => 'time',
            self::INDIVIDUAL_MASS => 'ranking',
            self::TEAM_VS_TEAM => 'win_loss',
            self::TEAM_MASS => 'ranking',
            self::INDIVIDUAL_VS_INDIVIDUAL => 'score',
            default => 'ranking',
        };
    }

    public static function label(?string $format): string
    {
        return match ($format) {
            self::INDIVIDUAL_HEAT => 'Individual Heat',
            self::INDIVIDUAL_MASS => 'Individual Mass',
            self::TEAM_VS_TEAM => 'Team vs Team',
            self::TEAM_MASS => 'Team Mass',
            self::INDIVIDUAL_VS_INDIVIDUAL => 'Individual vs Individual',
            default => 'Individual',
        };
    }
}
