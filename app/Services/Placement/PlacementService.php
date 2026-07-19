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

    public static function leastFilledRegu(?string $jenisKelamin = null): ?regu
    {
        $jenisKelaminFix = self::normalizeGender($jenisKelamin);

        return regu::where('jenis_kelamin', $jenisKelaminFix)
            ->withCount('peserta')
            ->orderBy('peserta_count')
            ->orderBy('id')
            ->first();
    }

    public static function leastFilledReguId(?string $jenisKelamin = null): ?int
    {
        return self::leastFilledRegu($jenisKelamin)?->id;
    }

    public static function leastFilledReguName(?string $jenisKelamin = null): string
    {
        return self::leastFilledRegu($jenisKelamin)?->regu ?? '-';
    }

    public static function autoPlacement(?string $jenisKelamin = null): array
    {
        $regu = self::leastFilledRegu($jenisKelamin);

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
