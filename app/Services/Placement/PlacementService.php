<?php

namespace App\Services\Placement;

use App\Models\peserta;

class PlacementService
{
    public static function generateParticipantNumber(?string $jenisKelamin = null): string
    {
        $prefix = self::genderPrefix($jenisKelamin);
        $last = peserta::query()
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
            $last = peserta::where(
                    'nip',
                    '>=',
                    1000
                )
                ->where(
                    'nip',
                    '<',
                    2000
                )
                ->max('nip');

            return $last
                ? ((int) $last + 1)
                : 1001;
        }

        if ($jk === 'perempuan') {
            $last = peserta::where(
                    'nip',
                    '>=',
                    2000
                )
                ->where(
                    'nip',
                    '<',
                    3000
                )
                ->max('nip');

            return $last
                ? ((int) $last + 1)
                : 2001;
        }

        return ((int) (peserta::max('nip') ?? 0)) + 1;
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
}
