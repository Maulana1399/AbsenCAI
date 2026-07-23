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

    public static function legacyNextNip(?string $jenisKelamin = null): int
    {
        $jk = strtolower(
            str_replace([' ', '-'], '', $jenisKelamin ?? '')
        );

        if ($jk === 'lakilaki') {
            $last = peserta::where('nip', '>=', 1000)
                ->where('nip', '<', 2000)
                ->max('nip');

            return $last
                ? ((int) $last + 1)
                : 1001;
        }

        if ($jk === 'perempuan') {
            $last = peserta::where('nip', '>=', 2000)
                ->where('nip', '<', 3000)
                ->max('nip');

            return $last
                ? ((int) $last + 1)
                : 2001;
        }

        return ((int) (peserta::max('nip') ?? 0)) + 1;
    }

    public static function leastFilledRegu(?string $jenisKelamin = null, ?int $eventId = null): ?regu
    {
        $jenisKelaminFix = self::normalizeGender($jenisKelamin);

        $query = regu::where('jenis_kelamin', $jenisKelaminFix);

        if ($eventId !== null) {
            $query->withCount(['participations' => fn ($q) => $q->where('event_id', $eventId)])
                ->orderBy('participations_count');
        } else {
            $query->withCount('peserta')
                ->orderBy('peserta_count');
        }

        return $query->orderBy('id')->first();
    }

    public static function leastFilledReguId(?string $jenisKelamin = null, ?int $eventId = null): ?int
    {
        return self::leastFilledRegu($jenisKelamin, $eventId)?->id;
    }

    public static function leastFilledReguName(?string $jenisKelamin = null, ?int $eventId = null): string
    {
        return self::leastFilledRegu($jenisKelamin, $eventId)?->regu ?? '-';
    }

    public static function autoPlacement(?string $jenisKelamin = null, ?int $eventId = null): array
    {
        $regu = self::leastFilledRegu($jenisKelamin, $eventId);

        return [
            'nip' => (string) self::legacyNextNip($jenisKelamin),
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
