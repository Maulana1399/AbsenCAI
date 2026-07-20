<?php

namespace App\Services\Pengajian;

use App\Models\EventAttendance;
use App\Models\Participation;
use App\Models\Person;
use App\Services\Placement\PlacementService;
use App\Services\Registration\RegistrationService;
use Illuminate\Support\Facades\DB;

class PengajianAttendanceService
{
    public function __construct(
        private readonly RegistrationService $registrationService,
    ) {}

    public function findOrCreateParticipation(Person $person, int $eventId, ?int $desaId = null): Participation
    {
        return DB::transaction(function () use ($person, $eventId, $desaId) {
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

            $targetDesaId = $locked->desa_id ?? $person->desa_id ?? $desaId;

            if ($targetDesaId !== null && $locked->desa_id === null) {
                $locked->update(['desa_id' => $targetDesaId]);
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
        ?array $metadata = null,
    ): EventAttendance {
        return EventAttendance::create([
            'participation_id' => $participation->id,
            'event_id' => $eventId,
            'desa_id' => $desaId,
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
}
