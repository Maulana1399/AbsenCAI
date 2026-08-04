<?php

namespace App\Services\Placement;

use App\Models\Participation;
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

    public static function leastFilledRegu(int $eventId, ?string $jenisKelamin = null): ?regu
    {
        $jenisKelaminFix = self::normalizeGender($jenisKelamin);

        return regu::where('jenis_kelamin', $jenisKelaminFix)
            ->withCount(['participations' => fn ($q) => $q->where('event_id', $eventId)])
            ->orderBy('participations_count')
            ->orderBy('id')
            ->first();
    }

    public static function leastFilledReguId(int $eventId, ?string $jenisKelamin = null): ?int
    {
        return self::leastFilledRegu($eventId, $jenisKelamin)?->id;
    }

    public static function leastFilledReguName(int $eventId, ?string $jenisKelamin = null): string
    {
        return self::leastFilledRegu($eventId, $jenisKelamin)?->regu ?? '-';
    }

    public static function autoPlacement(?string $jenisKelamin = null, ?int $eventId = null): array
    {
        $regu = $eventId !== null ? self::leastFilledRegu($eventId, $jenisKelamin) : null;

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
