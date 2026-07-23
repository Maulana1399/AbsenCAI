<?php

namespace App\Services\Placement;

use App\Models\Participation;
use App\Models\peserta;
use App\Models\regu;

class PlacementService
{
    public static function generateParticipantNumber(int $eventId, ?string $jenisKelamin = null): string
    {
        $prefix = self::genderPrefix($jenisKelamin);
        $last = Participation::query()
            ->where('event_id', $eventId)
            ->whereNotNull('participant_number')
            ->where('participant_number', 'like', $prefix.'%')
            ->max('participant_number');

        $nextNumber = $last
            ? ((int) substr($last, 2) + 1)
            : 1;

        return $prefix . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
    }

    public static function leastFilledRegu(?string $jenisKelamin = null, int $eventId): ?regu
    {
        $jenisKelaminFix = self::normalizeGender($jenisKelamin);

        return regu::where('jenis_kelamin', $jenisKelaminFix)
            ->withCount(['participations' => fn ($q) => $q->where('event_id', $eventId)])
            ->orderBy('participations_count')
            ->orderBy('id')
            ->first();
    }

    public static function leastFilledReguId(?string $jenisKelamin = null, int $eventId): ?int
    {
        return self::leastFilledRegu($jenisKelamin, $eventId)?->id;
    }

    public static function leastFilledReguName(?string $jenisKelamin = null, int $eventId): string
    {
        return self::leastFilledRegu($jenisKelamin, $eventId)?->regu ?? '-';
    }

    public static function autoPlacement(?string $jenisKelamin = null, ?int $eventId = null): array
    {
        // NIP generation retired per PGM.20. Use participant_number for
        // human-facing participant identity within an event.
        $regu = $eventId !== null ? self::leastFilledRegu($jenisKelamin, $eventId) : null;

        return [
            'regu_id' => $regu?->id,
            'regu_nama' => $regu?->regu ?? '-',
        ];
    }

    private static function genderPrefix(?string $jenisKelamin = null): string
    {
        $jk = strtolower(
            str_replace([' ', '-'], '', $jenisKelamin ?? '')
        );

        return match ($jk) {
            'lakilaki' => 'KL',
            'perempuan' => 'KP',
            default => 'KL',
        };
    }

    public static function normalizePersonGender(string $jenisKelamin): string
    {
        return match ($jenisKelamin) {
            'L' => 'Laki - Laki',
            'P' => 'Perempuan',
            default => $jenisKelamin,
        };
    }

    private static function normalizeGender(?string $jenisKelamin = null): ?string
    {
        $jk = strtolower(
            str_replace([' ', '-'], '', $jenisKelamin ?? '')
        );

        return match ($jk) {
            'lakilaki' => 'Laki - Laki',
            'perempuan' => 'Perempuan',
            default => $jenisKelamin,
        };
    }
}
