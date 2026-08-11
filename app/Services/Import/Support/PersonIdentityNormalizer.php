<?php

namespace App\Services\Import\Support;

use App\Models\desa;
use App\Models\kelompok;

/**
 * Shared Person field normalization (Design C). Used by Person and
 * Participation imports so identity logic is never duplicated.
 */
final class PersonIdentityNormalizer
{
    public function normalizeNama(mixed $value): string
    {
        $nama = trim((string) $value);
        $nama = preg_replace('/\s+/u', ' ', $nama) ?? $nama;

        if ($nama === '') {
            return $nama;
        }

        return $this->toTitleCase($nama);
    }

    /**
     * Canonical Proper/Title Case for human names.
     *
     * Base rule: every word gets an upper-cased first letter and a lower-cased
     * remainder ("REFIANTITO" → "Refiantito"). Special cases are preserved:
     *
     *  - Acronym/initialism tokens that are fully uppercase and longer than one
     *    character stay as-is ("FF", "AF") — but only when such tokens are the
     *    minority, i.e. the name was not typed predominantly in capitals
     *    (otherwise "BINTI CHUSNA" would never collapse to "Binti Chusna").
     *  - Hyphenated names are normalized per hyphen segment ("Al-Banie").
     *  - Apostrophe names (ASCII ') are normalized per apostrophe segment
     *    ("Rif'At"). Typographic apostrophes are left untouched.
     *  - Dot/initial tokens are normalized per dot segment and keep their dot
     *    structure ("Nuraini.R", "Nuraini.r" → "Nuraini.R", "A.").
     *
     * Duplicate detection stays case-insensitive because import duplicate keys
     * are always mb_strtolower'd before comparison.
     */
    private function toTitleCase(string $nama): string
    {
        $words = preg_split('/\s+/u', $nama);
        $isCapsDominant = $this->isCapsDominantName($words);

        return implode(' ', array_map(
            fn (string $word) => $this->normalizeWord($word, $isCapsDominant),
            $words,
        ));
    }

    /**
     * Decide whether a name was typed predominantly in capitals.
     *
     * A name typed in capitals ("BINTI CHUSNA", "WAHYUNI PUTRI NUR hidayah")
     * must be fully title cased so no token is mistaken for an acronym. When
     * fully-uppercase tokens (length > 1) are the minority ("Ahmad Agung FF"),
     * they are treated as intentional acronyms and preserved.
     *
     * @param  array<int, string>  $words
     */
    private function isCapsDominantName(array $words): bool
    {
        $capsTokens = 0;
        $otherTokens = 0;

        foreach ($words as $word) {
            if (! preg_match('/\p{L}/u', $word)) {
                continue;
            }

            if (mb_strlen($word, 'UTF-8') > 1 && mb_strtoupper($word, 'UTF-8') === $word) {
                $capsTokens++;
            } else {
                $otherTokens++;
            }
        }

        return $capsTokens > $otherTokens;
    }

    /**
     * Normalize a single whitespace-delimited word while keeping its internal
     * separator structure (hyphen, ASCII apostrophe, dot) intact.
     */
    private function normalizeWord(string $word, bool $isCapsDominant): string
    {
        $pieces = preg_split('~([-.\']+)~u', $word, -1, PREG_SPLIT_DELIM_CAPTURE);

        $normalized = array_map(
            fn (string $piece) => preg_match('~^[-.\']+$~u', $piece)
                ? $piece
                : $this->normalizeSegment($piece, $isCapsDominant),
            $pieces,
        );

        return implode('', $normalized);
    }

    /**
     * Normalize one segment: preserve fully-uppercase acronyms (length > 1)
     * unless the name is caps-dominant, otherwise apply Proper Case.
     */
    private function normalizeSegment(string $segment, bool $isCapsDominant): string
    {
        if ($segment === '') {
            return $segment;
        }

        if (! $isCapsDominant && mb_strlen($segment, 'UTF-8') > 1 && mb_strtoupper($segment, 'UTF-8') === $segment) {
            return $segment;
        }

        return mb_strtoupper(mb_substr($segment, 0, 1, 'UTF-8'), 'UTF-8')
            .mb_strtolower(mb_substr($segment, 1, null, 'UTF-8'), 'UTF-8');
    }

    /**
     * Gender normalization → canonical 'L' / 'P' (Person model).
     * Accepts L/P, Laki - Laki / Perempuan, and dash/case variants.
     */
    public function normalizeGender(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $compact = mb_strtolower(preg_replace('/[\s\-–—‑]+/u', '', $value) ?? $value);

        return match ($compact) {
            'l', 'lakilaki' => 'L',
            'p', 'perempuan' => 'P',
            default => $value,
        };
    }

    /**
     * Single source of truth for tanggal lahir normalization.
     *
     * Accepts every date representation the readers can produce (formatted Excel
     * strings, Excel serial numbers, DateTime/Carbon) and normalizes to canonical
     * `Y-m-d`. Empty values become null so the import validators can reject the
     * row as "tanggal lahir wajib diisi". Values that cannot be parsed are
     * returned unchanged so the validator can surface them as errors (never
     * silently reshaped).
     */
    public function normalizeTanggalLahir(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_int($value) || is_float($value)) {
            return $this->normalizeExcelSerial((float) $value);
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return $this->normalizeExcelSerial((float) $value);
        }

        foreach (['Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'd.m.Y'] as $format) {
            $date = $this->parseStrict($value, $format);

            if ($date !== null) {
                return $date->format('Y-m-d');
            }
        }

        return $value;
    }

    /**
     * Convert an Excel serial date number (e.g. 33044 → 1990-06-20).
     */
    private function normalizeExcelSerial(float $serial): string
    {
        try {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($serial)->format('Y-m-d');
        } catch (\Throwable) {
            return (string) $serial;
        }
    }

    /**
     * Strict date parse: exact format match, no overflow/warning, no silent
     * rollover (impossible dates such as 31/02/2000 are rejected).
     */
    private function parseStrict(string $value, string $format): ?\DateTime
    {
        $date = \DateTime::createFromFormat($format, $value);

        if ($date === false) {
            return null;
        }

        $errors = \DateTime::getLastErrors();

        if ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) {
            return null;
        }

        if ($date->format($format) !== $value) {
            return null;
        }

        return $date;
    }

    public function resolveDesaId(string $name): ?int
    {
        if ($name === '') {
            return null;
        }

        return desa::whereRaw('LOWER(TRIM(desa_asal)) = ?', [mb_strtolower($name)])->value('id');
    }

    public function resolveKelompokId(string $name, ?int $desaId): ?int
    {
        if ($name === '' || $desaId === null) {
            return null;
        }

        return kelompok::where('desa_id', $desaId)
            ->whereRaw('LOWER(TRIM(kelompok_asal)) = ?', [mb_strtolower($name)])
            ->value('id');
    }
}
