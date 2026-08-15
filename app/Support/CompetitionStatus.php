<?php

namespace App\Support;

/**
 * Competition (CompetitionClass) lifecycle status.
 *
 * Additive to `competition_classes.status`. Existing schedule-level statuses
 * (Scheduled/Ready/Playing/Waiting Result/Finished) are NOT touched.
 */
final class CompetitionStatus
{
    public const DRAFT = 'draft';

    public const REGISTRATION_OPEN = 'registration_open';

    public const REGISTRATION_CLOSED = 'registration_closed';

    public const READY = 'ready';

    public const RUNNING = 'running';

    public const FINISHED = 'finished';

    public const CANCELLED = 'cancelled';

    public const ALL = [
        self::DRAFT,
        self::REGISTRATION_OPEN,
        self::REGISTRATION_CLOSED,
        self::READY,
        self::RUNNING,
        self::FINISHED,
        self::CANCELLED,
    ];

    public static function isValid(?string $status): bool
    {
        return $status !== null && in_array($status, self::ALL, true);
    }

    public static function label(?string $status): string
    {
        return match ($status) {
            self::DRAFT => 'Draft',
            self::REGISTRATION_OPEN => 'Registrasi Dibuka',
            self::REGISTRATION_CLOSED => 'Registrasi Ditutup',
            self::READY => 'Siap',
            self::RUNNING => 'Berlangsung',
            self::FINISHED => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
            default => 'Registrasi Dibuka',
        };
    }
}
