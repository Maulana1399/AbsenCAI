<?php

namespace App\Services\Registration;

use App\Models\Event;
use App\Models\kelompok;
use App\Models\Participation;
use App\Models\Person;
use App\Services\Placement\PlacementService;
use Illuminate\Support\Facades\DB;

class ManualParticipantRegistrationService
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {}

    public function register(
        string $nama,
        string $jenisKelamin,
        ?string $tanggalLahir,
        int $desaId,
        int $eventId,
        ?int $kelompokId = null,
        ?int $forcePersonId = null,
        bool $forceCreateNew = false,
        ?string $jenisPeserta = 'Wajib',
        ?string $statusRegistrasi = null,
        ?int $reguId = null,
    ): array {
        return DB::transaction(function () use ($nama, $jenisKelamin, $tanggalLahir, $desaId, $eventId, $kelompokId, $forcePersonId, $forceCreateNew, $jenisPeserta, $statusRegistrasi, $reguId) {
            Event::lockForUpdate()->findOrFail($eventId);

            if ($kelompokId !== null) {
                $this->assertKelompokBelongsToDesa($kelompokId, $desaId);
            }

            if ($forcePersonId !== null) {
                $person = Person::lockForUpdate()->findOrFail($forcePersonId);
                $this->assertPersonBelongsToDesa($person, $desaId);

                return $this->resolveParticipation($person, $eventId, $jenisKelamin, 'matched', $jenisPeserta, $statusRegistrasi, $reguId);
            }

            if ($forceCreateNew) {
                $person = $this->createPerson($nama, $jenisKelamin, $tanggalLahir, $desaId, $kelompokId);

                return $this->resolveParticipation($person, $eventId, $jenisKelamin, 'created', $jenisPeserta, $statusRegistrasi, $reguId);
            }

            $resolution = $this->resolvePerson($nama, $desaId, $tanggalLahir);

            if ($resolution['status'] === 'exact') {
                return $this->resolveParticipation($resolution['person'], $eventId, $jenisKelamin, 'matched', $jenisPeserta, $statusRegistrasi, $reguId);
            }

            if ($resolution['status'] === 'ambiguous') {
                $potential = collect($resolution['potential_matches'])
                    ->map(fn (Person $p) => [
                        'id' => $p->id,
                        'nama' => $p->nama,
                        'jenis_kelamin' => $p->jenis_kelamin,
                        'tanggal_lahir' => $p->tanggal_lahir?->format('Y-m-d'),
                    ])
                    ->toArray();

                return [
                    'status' => 'ambiguous',
                    'person' => null,
                    'participation' => null,
                    'message' => 'Ditemukan peserta dengan nama yang mirip. Verifikasi data.',
                    'potential_matches' => $potential,
                ];
            }

            $person = $this->createPerson($nama, $jenisKelamin, $tanggalLahir, $desaId, $kelompokId);

            return $this->resolveParticipation($person, $eventId, $jenisKelamin, 'created', $jenisPeserta, $statusRegistrasi, $reguId);
        });
    }

    /**
     * Resolve an existing Person by canonical identity (normalized nama + desa
     * + tanggal lahir) following the Design C business rule.
     *
     * @return array{person: ?Person, status: string, potential_matches?: \Illuminate\Support\Collection<int, Person>}
     */
    public function resolvePerson(string $nama, int $desaId, ?string $tanggalLahir): array
    {
        $normalized = $this->normalizeNama($nama);
        $candidates = Person::where('desa_id', $desaId)
            ->whereRaw('LOWER(TRIM(nama)) = ?', [$normalized])
            ->get();

        if ($candidates->isEmpty()) {
            return ['person' => null, 'status' => 'new'];
        }

        $hasBirthDate = $tanggalLahir !== null && $tanggalLahir !== '';

        if (! $hasBirthDate) {
            return ['person' => null, 'status' => 'ambiguous', 'potential_matches' => $candidates];
        }

        $exact = $candidates->first(fn (Person $p) => $p->tanggal_lahir?->format('Y-m-d') === $tanggalLahir
        );

        if ($exact !== null) {
            return ['person' => $exact, 'status' => 'exact'];
        }

        return ['person' => null, 'status' => 'new'];
    }

    private function resolveParticipation(Person $person, int $eventId, string $jenisKelamin, string $status, string $jenisPeserta = 'Wajib', ?string $statusRegistrasi = null, ?int $reguId = null): array
    {
        $existing = Participation::where('person_id', $person->id)
            ->where('event_id', $eventId)
            ->first();

        if ($existing !== null) {
            return [
                'status' => 'duplicate',
                'person' => $person,
                'participation' => $existing,
                'message' => 'Peserta sudah terdaftar di event ini.',
                'potential_matches' => [],
            ];
        }

        $participation = $this->createParticipation($person, $eventId, $jenisKelamin, $jenisPeserta, $statusRegistrasi, $reguId);

        $message = $status === 'created'
            ? 'Peserta baru berhasil ditambahkan.'
            : 'Peserta berhasil ditambahkan.';

        return [
            'status' => $status,
            'person' => $person,
            'participation' => $participation,
            'message' => $message,
            'potential_matches' => [],
        ];
    }

    private function createParticipation(Person $person, int $eventId, string $jenisKelamin, string $jenisPeserta = 'Wajib', ?string $statusRegistrasi = null, ?int $reguId = null): Participation
    {
        $gender = PlacementService::normalizePersonGender($person->jenis_kelamin ?? $jenisKelamin);
        $participantNumber = PlacementService::generateParticipantNumber($eventId, $gender);
        $attendanceCode = $this->registrationService->generateAttendanceCode();

        return Participation::create([
            'person_id' => $person->id,
            'event_id' => $eventId,
            'participant_number' => $participantNumber,
            'attendance_code' => $attendanceCode,
            'jenis_peserta' => $jenisPeserta,
            'status_registrasi' => $statusRegistrasi,
            'regu_id' => $reguId,
        ]);
    }

    private function createPerson(string $nama, string $jenisKelamin, ?string $tanggalLahir, int $desaId, ?int $kelompokId = null): Person
    {
        return Person::create([
            'nama' => trim($nama),
            'jenis_kelamin' => $jenisKelamin,
            'tanggal_lahir' => $tanggalLahir ?: null,
            'desa_id' => $desaId,
            'kelompok_id' => $kelompokId,
            'nip' => null,
        ]);
    }

    private function assertPersonBelongsToDesa(Person $person, int $desaId): void
    {
        if ((int) $person->desa_id !== $desaId) {
            throw new \RuntimeException('Person does not belong to this desa.');
        }
    }

    private function assertKelompokBelongsToDesa(int $kelompokId, int $desaId): void
    {
        $kelompok = kelompok::find($kelompokId);

        if ($kelompok === null) {
            throw new \RuntimeException('Kelompok tidak ditemukan.');
        }

        if ((int) $kelompok->desa_id !== $desaId) {
            throw new \RuntimeException('Kelompok tidak berada dalam desa yang sesuai.');
        }
    }

    private function normalizeNama(string $nama): string
    {
        return trim(mb_strtolower(preg_replace('/\s+/', ' ', $nama)));
    }
}
