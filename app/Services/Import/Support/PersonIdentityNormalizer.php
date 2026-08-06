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

        return $nama;
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
     * Returns the value unchanged (valid YYYY-MM-DD or not); the validator
     * decides validity so invalid values are surfaced as errors.
     */
    public function normalizeTanggalLahir(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
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
