<?php

namespace App\Services\Pengajian;

use App\Models\desa;
use App\Models\kelompok;
use App\Models\Participation;
use App\Models\Person;
use App\Services\Placement\PlacementService;
use App\Services\Registration\RegistrationService;
use Illuminate\Support\Facades\DB;

class PengajianImportService
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {}

    public function import(
        array $rows,
        int $eventId,
    ): array {
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
                $this->processRow($row, $eventId, $index, $result);
            } catch (\Throwable $e) {
                $result['failed_rows']++;
                $result['errors'][] = [
                    'row' => $index + 2,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $result;
    }

    public function validate(array $rows): array
    {
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $rowErrors = [];

            if (empty(trim($row['nama'] ?? ''))) {
                $rowErrors[] = 'Nama wajib diisi.';
            }

            if (empty($row['jenis_kelamin'])) {
                $rowErrors[] = 'Jenis kelamin wajib diisi.';
            } elseif (! in_array(strtoupper($row['jenis_kelamin']), ['L', 'P'])) {
                $rowErrors[] = 'Jenis kelamin harus L atau P.';
            }

            if (empty($row['tanggal_lahir'])) {
                $rowErrors[] = 'Tanggal lahir wajib diisi.';
            } elseif (! $this->isValidDate($row['tanggal_lahir'])) {
                $rowErrors[] = 'Format tanggal lahir tidak valid (YYYY-MM-DD).';
            }

            if (empty(trim($row['desa'] ?? ''))) {
                $rowErrors[] = 'Desa wajib diisi.';
            }

            if (! empty($rowErrors)) {
                $errors[] = [
                    'row' => $rowNumber,
                    'errors' => $rowErrors,
                ];
            }
        }

        return $errors;
    }

    private function processRow(array $row, int $eventId, int $index, array &$result): void
    {
        $nama = trim($row['nama'] ?? '');
        $jenisKelamin = strtoupper(trim($row['jenis_kelamin'] ?? ''));
        $tanggalLahir = trim($row['tanggal_lahir'] ?? '');
        $desaName = trim($row['desa'] ?? '');
        $kelompokName = trim($row['kelompok'] ?? '');

        if ($nama === '') {
            $result['failed_rows']++;
            $result['errors'][] = [
                'row' => $index + 2,
                'message' => 'Nama wajib diisi.',
            ];

            return;
        }

        if ($jenisKelamin === '' || ! in_array($jenisKelamin, ['L', 'P'])) {
            $result['failed_rows']++;
            $result['errors'][] = [
                'row' => $index + 2,
                'message' => 'Jenis kelamin harus L atau P.',
            ];

            return;
        }

        if ($tanggalLahir === '' || ! $this->isValidDate($tanggalLahir)) {
            $result['failed_rows']++;
            $result['errors'][] = [
                'row' => $index + 2,
                'message' => 'Tanggal lahir wajib diisi dengan format YYYY-MM-DD yang valid.',
            ];

            return;
        }

        if ($desaName === '') {
            $result['failed_rows']++;
            $result['errors'][] = [
                'row' => $index + 2,
                'message' => 'Desa wajib diisi.',
            ];

            return;
        }

        $resolvedDesa = desa::whereRaw('LOWER(TRIM(desa_asal)) = ?', [strtolower($desaName)])->first();

        if ($resolvedDesa === null) {
            $result['failed_rows']++;
            $result['errors'][] = [
                'row' => $index + 2,
                'message' => "Desa '{$desaName}' tidak ditemukan.",
            ];

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
                    $result['failed_rows']++;
                    $result['errors'][] = [
                        'row' => $index + 2,
                        'message' => "Kelompok '{$kelompokName}' tidak berada di Desa '{$desaName}'.",
                    ];
                } else {
                    $result['failed_rows']++;
                    $result['errors'][] = [
                        'row' => $index + 2,
                        'message' => "Kelompok '{$kelompokName}' tidak ditemukan.",
                    ];
                }

                return;
            }
        }

        DB::transaction(function () use ($nama, $jenisKelamin, $tanggalLahir, $resolvedDesa, $resolvedKelompok, $eventId, &$result) {
            $normalized = $this->normalizeNama($nama);
            $candidates = Person::where('desa_id', $resolvedDesa->id)
                ->whereRaw('LOWER(TRIM(nama)) = ?', [$normalized])
                ->get();

            $person = null;
            $isNewPerson = false;

            $existingPerson = $candidates->first(fn (Person $p) => $p->tanggal_lahir?->format('Y-m-d') === $tanggalLahir
            );

            if ($existingPerson !== null) {
                $person = $existingPerson;
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
                $isNewPerson = true;
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

    private function normalizeNama(string $nama): string
    {
        return trim(mb_strtolower(preg_replace('/\s+/', ' ', $nama)));
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);

        return $d && $d->format('Y-m-d') === $date;
    }
}
