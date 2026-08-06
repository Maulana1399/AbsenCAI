<?php

namespace App\Services\Import\Adapters\Peserta;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\peserta;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use App\Services\Placement\PlacementService;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;

/**
 * Peserta committer — faithful port of the legacy Maatwebsite `PesertaImport::model()`:
 * resolve desa/kelompok (case-insensitive), auto-place regu, then delegate to the
 * canonical RegistrationService::createParticipant (creates peserta + Person +
 * Participation + legacy mappings). NO Excel::import.
 */
final class PesertaImportCommitter implements ImportCommitter
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {}

    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function commit(array $rows, ImportContext $context): ImportCommit
    {
        $createdIds = [];
        $failedRows = [];

        foreach ($rows as $row) {
            $data = $row->data;

            if (empty(trim($data['nama'] ?? ''))) {
                continue; // legacy: rows without nama are skipped (model() returns null)
            }

            try {
                $kelompok = kelompok::whereRaw('LOWER(TRIM(kelompok_asal)) = ?', [strtolower(trim($data['kelompok'] ?? ''))])->first();
                $desa = desa::whereRaw('LOWER(TRIM(desa_asal)) = ?', [strtolower(trim($data['desa'] ?? ''))])->first();
                $jenisKelamin = $data['jenis_kelamin'] ?? null;
                $eventId = app(ActiveEventContext::class)->id();
                $autoPlacement = PlacementService::autoPlacement($jenisKelamin, $eventId);

                $peserta = $this->registrationService->createParticipant([
                    'nama' => trim($data['nama']),
                    'jenis_kelamin' => $jenisKelamin,
                    'jenis_peserta' => $data['jenis_peserta'] ?? peserta::JENIS_KIRIMAN,
                    'regu_id' => $autoPlacement['regu_id'],
                    'kelompok_id' => $kelompok?->id,
                    'desa_id' => $desa?->id,
                    'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
                ]);
                $createdIds[] = $peserta->id;
            } catch (\Throwable $e) {
                $failedRows[] = [
                    'row' => $row->rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return new ImportCommit(
            context: $context,
            summary: new ImportSummary(
                totalRows: count($rows),
                createdRows: count($createdIds),
                invalidRows: count($failedRows),
            ),
            createdIds: $createdIds,
            failedRows: $failedRows,
        );
    }
}
