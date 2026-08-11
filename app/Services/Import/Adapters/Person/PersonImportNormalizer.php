<?php

namespace App\Services\Import\Adapters\Person;

use App\Services\Import\Contracts\ImportNormalizer;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;
use App\Services\Import\Support\PersonIdentityNormalizer;

final class PersonImportNormalizer implements ImportNormalizer
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
            $nama = $this->personIdentity->normalizeNama($row->raw['nama'] ?? '');
            $jenisKelamin = $this->personIdentity->normalizeGender($row->raw['jenis_kelamin'] ?? null);
            $tanggalLahir = $this->personIdentity->normalizeTanggalLahir($row->raw['tanggal_lahir'] ?? '');

            $desaName = trim((string) ($row->raw['desa'] ?? ''));
            $kelompokName = trim((string) ($row->raw['kelompok'] ?? ''));

            $desaId = $this->personIdentity->resolveDesaId($desaName);
            $kelompokId = $this->personIdentity->resolveKelompokId($kelompokName, $desaId);

            $duplicateKey = ($nama !== '' && $desaId !== null && $tanggalLahir !== null)
                ? mb_strtolower($nama).'|'.$desaId.'|'.$tanggalLahir
                : null;

            return new NormalizedImportRow(
                rowNumber: $row->rowNumber,
                data: [
                    'nama' => $nama,
                    'jenis_kelamin' => $jenisKelamin,
                    'tanggal_lahir' => $tanggalLahir,
                    'desa' => $desaName,
                    'desa_id' => $desaId,
                    'kelompok' => $kelompokName,
                    'kelompok_id' => $kelompokId,
                ],
                original: $row,
                duplicateKey: $duplicateKey,
            );
        }, $rows);
    }
}
