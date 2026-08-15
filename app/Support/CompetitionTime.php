<?php

namespace App\Support;

/**
 * Time representation for Competition result_type = time.
 *
 * Stored as decimal seconds in `score`; displayed/parsed as M:SS.mmm.
 * Accepts: "92.5", "1:32.5", "01:32.50", "1:32.500".
 */
final class CompetitionTime
{
    public static function parse(?string $input): ?float
    {
        if ($input === null) {
            return null;
        }

        $value = trim((string) $input);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(?:(\d{1,3}):)?(\d{1,2})(?:[.,](\d{1,3}))?$/', $value, $m)) {
            $minutes = isset($m[1]) ? (int) $m[1] : 0;
            $seconds = (int) $m[2];
            $fraction = isset($m[3]) ? ((int) str_pad($m[3], 3, '0')) / 1000 : 0.0;

            return round($minutes * 60 + $seconds + $fraction, 2);
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        return null;
    }

    public static function format(?float $seconds): string
    {
        if ($seconds === null) {
            return '';
        }

        $total = max(0.0, round((float) $seconds, 3));
        $minutes = (int) floor($total / 60);
        $sec = $total - ($minutes * 60);

        return sprintf('%d:%06.3f', $minutes, $sec);
    }
}
