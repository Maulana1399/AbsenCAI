<?php

namespace App\Services\Import\Adapters\Participation;

use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportWarning;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use App\Services\Registration\ManualParticipantRegistrationService;

final class ParticipationImportCommitter implements ImportCommitter
{
    public function __construct(
        private readonly ManualParticipantRegistrationService $registrationService,
    ) {}

    /**
     * Each row delegates to the canonical ManualParticipantRegistrationService:
     * Person lookup first (never blindly creates a Person), then Participation
     * creation with per-event duplicate detection.
     *
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function commit(array $rows, ImportContext $context): ImportCommit
    {
        $eventId = (int) ($context->options['parameters']['event_id'] ?? 0);
        $createdIds = [];
        $skippedIds = [];
        $failedRows = [];
        $warnings = [];

        foreach ($rows as $row) {
            $nama = trim($row->data['nama'] ?? '');
            $gender = $row->data['jenis_kelamin'] ?? null;
            $desaId = $row->data['desa_id'] ?? null;

            if ($nama === '' || ! in_array($gender, ['L', 'P'], true) || $desaId === null || $eventId <= 0) {
                $failedRows[] = [
                    'row' => $row->rowNumber,
                    'message' => 'Data identitas tidak lengkap untuk registrasi.',
                ];

                continue;
            }

            try {
                $result = $this->registrationService->register(
                    nama: $nama,
                    jenisKelamin: $gender,
                    tanggalLahir: $row->data['tanggal_lahir'] ?? null,
                    desaId: (int) $desaId,
                    eventId: $eventId,
                    kelompokId: $row->data['kelompok_id'] ?? null,
                    jenisPeserta: ($row->data['jenis_peserta'] ?? '') !== '' ? $row->data['jenis_peserta'] : 'Wajib',
                    statusRegistrasi: ($row->data['status_registrasi'] ?? '') !== '' ? $row->data['status_registrasi'] : null,
                    reguId: $row->data['regu_id'] ?? null,
                );
            } catch (\Throwable $e) {
                $failedRows[] = [
                    'row' => $row->rowNumber,
                    'message' => $e->getMessage(),
                ];

                continue;
            }

            switch ($result['status']) {
                case 'created':
                case 'matched':
                    if (isset($result['participation'])) {
                        $createdIds[] = $result['participation']->id;
                    }
                    break;

                case 'duplicate':
                    $skippedIds[] = $row->rowNumber;
                    break;

                case 'ambiguous':
                    $warnings[] = new ImportWarning(
                        rowNumber: $row->rowNumber,
                        field: 'nama',
                        message: $result['message'],
                    );
                    break;
            }
        }

        return new ImportCommit(
            context: $context,
            summary: new ImportSummary(
                totalRows: count($rows),
                createdRows: count($createdIds),
                skippedRows: count($skippedIds),
                invalidRows: count($failedRows),
                warnings: $warnings,
            ),
            createdIds: $createdIds,
            skippedIds: $skippedIds,
            failedRows: $failedRows,
        );
    }
}
