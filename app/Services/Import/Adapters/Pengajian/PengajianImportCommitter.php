<?php

namespace App\Services\Import\Adapters\Pengajian;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\Participation;
use App\Models\Person;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use App\Services\Placement\PlacementService;
use App\Services\Registration\ManualParticipantRegistrationService;
use App\Services\Registration\RegistrationService;
use Illuminate\Support\Facades\DB;

/**
 * Pengajian committer — faithful port of the golden `PengajianImportService`
 * per-row flow. Counters, order, messages, transactions and writes are
 * IDENTICAL. Person lookup reuses the canonical service; identifiers come from
 * PlacementService / RegistrationService.
 */
final class PengajianImportCommitter implements ImportCommitter
{
    public function __construct(
        private readonly ManualParticipantRegistrationService $personService,
        private readonly RegistrationService $registrationService,
    ) {}

    /**
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function commit(array $rows, ImportContext $context): ImportCommit
    {
        $eventId = $context->eventId ?? 0;
        $result = [
            'created_persons' => 0,
            'matched_persons' => 0,
            'created_participations' => 0,
            'skipped_duplicates' => 0,
            'failed_rows' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            try {
                $this->processRow($row, $eventId, $result);
            } catch (\Throwable $e) {
                $result['failed_rows']++;
                $result['errors'][] = [
                    'row' => $row->rowNumber,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return new ImportCommit(
            context: $context,
            summary: new ImportSummary(
                totalRows: count($rows),
                createdRows: $result['created_participations'],
                skippedRows: $result['skipped_duplicates'],
                invalidRows: $result['failed_rows'],
            ),
            failedRows: $result['errors'],
            metrics: [
                'created_persons' => $result['created_persons'],
                'matched_persons' => $result['matched_persons'],
                'created_participations' => $result['created_participations'],
                'skipped_duplicates' => $result['skipped_duplicates'],
                'failed_rows' => $result['failed_rows'],
            ],
        );
    }

    private function processRow(NormalizedImportRow $row, int $eventId, array &$result): void
    {
        $data = $row->data;
        $rowNumber = $row->rowNumber;
        $nama = trim($data['nama'] ?? '');
        $jenisKelamin = strtoupper(trim($data['jenis_kelamin'] ?? ''));
        $tanggalLahir = trim($data['tanggal_lahir'] ?? '');
        $desaName = trim($data['desa'] ?? '');
        $kelompokName = trim($data['kelompok'] ?? '');

        if ($nama === '') {
            $this->fail($result, $rowNumber, 'Nama wajib diisi.');

            return;
        }

        if ($jenisKelamin === '' || ! in_array($jenisKelamin, ['L', 'P'])) {
            $this->fail($result, $rowNumber, 'Jenis kelamin harus L atau P.');

            return;
        }

        if ($tanggalLahir === '' || ! $this->isValidDate($tanggalLahir)) {
            $this->fail($result, $rowNumber, 'Tanggal lahir wajib diisi dengan format YYYY-MM-DD yang valid.');

            return;
        }

        if ($desaName === '') {
            $this->fail($result, $rowNumber, 'Desa wajib diisi.');

            return;
        }

        $resolvedDesa = desa::whereRaw('LOWER(TRIM(desa_asal)) = ?', [strtolower($desaName)])->first();

        if ($resolvedDesa === null) {
            $this->fail($result, $rowNumber, "Desa '{$desaName}' tidak ditemukan.");

            return;
        }

        $resolvedKelompok = null;

        if ($kelompokName !== '') {
            $resolvedKelompok = kelompok::where('desa_id', $resolvedDesa->id)
                ->whereRaw('LOWER(TRIM(kelompok_asal)) = ?', [strtolower($kelompokName)])
                ->first();

            if ($resolvedKelompok === null) {
                $notFound = kelompok::whereRaw('LOWER(TRIM(kelompok_asal)) = ?', [strtolower($kelompokName)])->exists();

                if ($notFound) {
                    $this->fail($result, $rowNumber, "Kelompok '{$kelompokName}' tidak berada di Desa '{$desaName}'.");
                } else {
                    $this->fail($result, $rowNumber, "Kelompok '{$kelompokName}' tidak ditemukan.");
                }

                return;
            }
        }

        DB::transaction(function () use ($nama, $jenisKelamin, $tanggalLahir, $resolvedDesa, $resolvedKelompok, $eventId, &$result) {
            $resolution = $this->personService->resolvePerson($nama, $resolvedDesa->id, $tanggalLahir);

            if ($resolution['status'] === 'exact') {
                $person = $resolution['person'];
                $result['matched_persons']++;
            } else {
                $person = Person::create([
                    'nama' => $nama,
                    'jenis_kelamin' => $jenisKelamin,
                    'tanggal_lahir' => $tanggalLahir,
                    'desa_id' => $resolvedDesa->id,
                    'kelompok_id' => $resolvedKelompok?->id,
                    'nip' => null,
                ]);
                $result['created_persons']++;
            }

            $existingParticipation = Participation::where('person_id', $person->id)
                ->where('event_id', $eventId)
                ->first();

            if ($existingParticipation !== null) {
                $result['skipped_duplicates']++;

                return;
            }

            $gender = PlacementService::normalizePersonGender($person->jenis_kelamin ?? $jenisKelamin);
            $participantNumber = PlacementService::generateParticipantNumber($eventId, $gender);
            $attendanceCode = $this->registrationService->generateAttendanceCode();

            Participation::create([
                'person_id' => $person->id,
                'event_id' => $eventId,
                'participant_number' => $participantNumber,
                'attendance_code' => $attendanceCode,
                'jenis_peserta' => 'Pengajian Desa',
            ]);
            $result['created_participations']++;
        });
    }

    private function fail(array &$result, int $rowNumber, string $message): void
    {
        $result['failed_rows']++;
        $result['errors'][] = [
            'row' => $rowNumber,
            'message' => $message,
        ];
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);

        return $d !== false && $d->format('Y-m-d') === $date;
    }
}
