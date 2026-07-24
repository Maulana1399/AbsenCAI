<?php

namespace App\Services\Pengajian;

use App\Models\DesaAccessGrant;
use App\Models\EventAttendance;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Services\Placement\PlacementService;
use App\Services\Registration\RegistrationService;
use Illuminate\Support\Facades\DB;

class PengajianAttendanceService
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {}

    public function findOrCreateParticipation(Person $person, int $eventId, ?int $desaId = null, bool $allowDesaAutoAssign = true): Participation
    {
        return DB::transaction(function () use ($person, $eventId, $desaId, $allowDesaAutoAssign) {
            $locked = Person::query()
                ->lockForUpdate()
                ->findOrFail($person->id);

            $participation = Participation::query()
                ->where('person_id', $locked->id)
                ->where('event_id', $eventId)
                ->first();

            if ($participation) {
                return $participation;
            }

            if ($allowDesaAutoAssign) {
                $targetDesaId = $locked->desa_id ?? $person->desa_id ?? $desaId;

                if ($targetDesaId !== null && $locked->desa_id === null) {
                    $locked->update(['desa_id' => $targetDesaId]);
                }
            }

            $gender = PlacementService::normalizePersonGender($locked->jenis_kelamin);

            $participantNumber = PlacementService::generateParticipantNumber($eventId, $gender);

            $attendanceCode = $this->registrationService->generateAttendanceCode();

            return Participation::create([
                'person_id' => $locked->id,
                'event_id' => $eventId,
                'participant_number' => $participantNumber,
                'attendance_code' => $attendanceCode,
                'jenis_peserta' => 'Pengajian Desa',
            ]);
        });
    }

    public function recordAttendance(
        Participation $participation,
        int $eventId,
        ?int $desaId,
        string $method = 'offline',
        ?int $recordedBy = null,
        ?string $status = null,
        ?array $metadata = null,
    ): EventAttendance {
        return EventAttendance::create([
            'participation_id' => $participation->id,
            'event_id' => $eventId,
            'desa_id' => $desaId,
            'status' => $status ?? EventAttendance::STATUS_HADIR,
            'attended_at' => now(),
            'method' => $method,
            'recorded_by' => $recordedBy,
            'metadata' => $metadata,
        ]);
    }

    public function attendPerson(Person $person, int $eventId, ?int $desaId, ?int $recordedBy = null): EventAttendance
    {
        $participation = $this->findOrCreateParticipation($person, $eventId, $desaId);

        return $this->recordAttendance(
            $participation, $eventId, $desaId,
            recordedBy: $recordedBy,
        );
    }

    public function attendPersonPublicContext(Person $person, DesaAccessGrant $grant): EventAttendance
    {
        if (! $grant->isNonceValid()) {
            throw new \RuntimeException('Sesi QR tidak valid atau sudah kedaluwarsa.');
        }

        if ($person->desa_id === null) {
            throw new \RuntimeException('Person tidak memiliki desa assignment.');
        }

        if ((int) $person->desa_id !== (int) $grant->desa_id) {
            throw new \RuntimeException('Person tidak terdaftar di desa ini.');
        }

        $participation = $this->findOrCreateParticipation(
            $person, $grant->event_id, $grant->desa_id,
            allowDesaAutoAssign: false,
        );

        $existing = EventAttendance::query()
            ->where('participation_id', $participation->id)
            ->where('status', EventAttendance::STATUS_HADIR)
            ->first();

        if ($existing) {
            throw new \RuntimeException('Peserta sudah tercatat hadir.');
        }

        $existingIzin = EventAttendance::query()
            ->where('participation_id', $participation->id)
            ->where('status', EventAttendance::STATUS_IZIN)
            ->first();

        if ($existingIzin) {
            throw new \RuntimeException('Peserta sudah tercatat izin.');
        }

        return $this->recordAttendance(
            $participation, $grant->event_id, $grant->desa_id,
            method: 'self',
            recordedBy: null,
        );
    }

    public function attendPersonOperatorContext(
        Person $person,
        DesaAccessGrant $grant,
        ?User $recordedBy = null,
        ?string $status = null,
    ): EventAttendance {
        if (! $grant->isValid()) {
            throw new \RuntimeException('Sesi akses tidak valid. Silakan hubungi Operator Daerah.');
        }

        if ($person->desa_id === null) {
            throw new \RuntimeException('Person tidak memiliki desa assignment.');
        }

        if ((int) $person->desa_id !== (int) $grant->desa_id) {
            throw new \RuntimeException('Person tidak terdaftar di desa ini.');
        }

        $participation = $this->findOrCreateParticipation(
            $person, $grant->event_id, $grant->desa_id,
            allowDesaAutoAssign: false,
        );

        $existingHadir = EventAttendance::query()
            ->where('participation_id', $participation->id)
            ->where('status', EventAttendance::STATUS_HADIR)
            ->first();

        if ($existingHadir) {
            throw new \RuntimeException('Peserta sudah tercatat hadir.');
        }

        $existingIzin = EventAttendance::query()
            ->where('participation_id', $participation->id)
            ->where('status', EventAttendance::STATUS_IZIN)
            ->first();

        if ($existingIzin) {
            throw new \RuntimeException('Peserta sudah tercatat izin.');
        }

        return $this->recordAttendance(
            $participation, $grant->event_id, $grant->desa_id,
            method: 'operator',
            recordedBy: $recordedBy?->id,
            status: $status,
        );
    }
}
