<?php

namespace App\Services\Placement;

use App\Models\peserta;

class PlacementService
{
    public static function generateParticipantNumber(?string $jenisKelamin = null): int
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
}
