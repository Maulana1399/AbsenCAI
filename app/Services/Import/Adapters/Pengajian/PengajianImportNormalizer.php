<?php

namespace App\Services\Import\Adapters\Pengajian;

use App\Services\Import\Contracts\ImportNormalizer;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;
use App\Services\Import\Support\PersonIdentityNormalizer;

/**
 * Pengajian normalizer — only `tanggal_lahir` is normalized so valid Excel
 * date representations (DD/MM/YYYY, DD-MM-YYYY, Excel native/serial, …) reach
 * the strict validator as canonical `Y-m-d`. Empty dates stay null so the
 * validator rejects the row (tanggal lahir wajib diisi). Every other field
 * stays raw: the golden validator (strtoupper gender) and committer (trim)
 * keep their exact behavior. The date normalization reuses the shared
 * PersonIdentityNormalizer (single source of truth) — no duplicate logic.
 */
final class PengajianImportNormalizer implements ImportNormalizer
{
    public function __construct(
        private readonly PersonIdentityNormalizer $personIdentity,
    ) {}

    /**
     * @param  array<int, RawImportRow>  $rows
     * @return array<int, NormalizedImportRow>
     */
    public function normalize(array $rows, ImportContext $context): array
    {
        return array_map(function (RawImportRow $row) {
            return new NormalizedImportRow(
                $row->rowNumber,
                [...$row->raw, 'tanggal_lahir' => $this->personIdentity->normalizeTanggalLahir($row->raw['tanggal_lahir'] ?? '')],
                $row,
            );
        }, $rows);
    }
}
