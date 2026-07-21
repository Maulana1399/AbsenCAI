<?php

namespace App\Services\Registration;

use App\Models\Event;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Services\Placement\PlacementService;
use App\Support\ActiveEventContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function createParticipant(array $data): peserta
    {
        try {
            return DB::transaction(function () use ($data) {
                $event = $this->activeEvent();
                $participantNumber = $data['participant_number'] ?? $this->nextParticipantNumber($event->id, $data['jenis_kelamin'] ?? null);
                $attendanceCode = $data['attendance_code'] ?? $this->generateAttendanceCode();

                $this->ensureUniqueForEvent($event->id, $participantNumber);
                $this->ensureUniqueAttendanceCode($attendanceCode);

                $peserta = peserta::create([
                    'nama' => $data['nama'],
                    'nip' => $data['nip'],
                    'participant_number' => $participantNumber,
                    'attendance_code' => $attendanceCode,
                    'jenis_kelamin' => $data['jenis_kelamin'],
                    'jenis_peserta' => $data['jenis_peserta'],
                    'desa_id' => $data['desa_id'],
                    'kelompok_id' => $data['kelompok_id'],
                    'regu_id' => $data['regu_id'],
                    'status_registrasi' => $data['status_registrasi'],
                ]);

                $person = Person::create([
                    'nama' => $data['nama'],
                    'nip' => $data['nip'],
                    'jenis_kelamin' => $data['jenis_kelamin'] === 'Perempuan' ? 'P' : 'L',
                    'desa_id' => $data['desa_id'],
                    'kelompok_id' => $data['kelompok_id'] ?? null,
                ]);

                $participation = Participation::create([
                    'person_id' => $person->id,
                    'event_id' => $event->id,
                    'participant_number' => $participantNumber,
                    'attendance_code' => $attendanceCode,
                    'jenis_peserta' => $data['jenis_peserta'],
                ]);

                LegacyPesertaMapping::create([
                    'peserta_id' => $peserta->id,
                    'person_id' => $person->id,
                    'participation_id' => $participation->id,
                    'event_id' => $event->id,
                    'legacy_nip' => $peserta->nip,
                    'legacy_participant_number' => $participantNumber,
                    'legacy_attendance_code' => $attendanceCode,
                    'migrated_at' => now(),
                ]);

                return $peserta->refresh();
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                throw ValidationException::withMessages([
                    'nama' => 'Peserta dengan nama, desa, dan kelompok ini sudah terdaftar.',
                ]);
            }

            throw $exception;
        }
    }

    public function updateParticipantStatus(int $id, string $status): peserta
    {
        $peserta = peserta::findOrFail($id);

        $peserta->update([
            'status_registrasi' => $status,
        ]);

        return $peserta;
    }

    public function updateParticipant(int $id, array $data): peserta
    {
        return DB::transaction(function () use ($id, $data) {
            $peserta = peserta::findOrFail($id);
            $mapping = $peserta->legacyPesertaMapping()->with('participation')->first();

            $peserta->update([
                'nama' => $data['nama'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'jenis_peserta' => $data['jenis_peserta'],
                'desa_id' => $data['desa_id'],
                'kelompok_id' => $data['kelompok_id'],
                'regu_id' => $data['regu_id'],
            ]);

            if ($mapping?->participation !== null) {
                $mapping->participation->update([
                    'jenis_peserta' => $data['jenis_peserta'],
                ]);

                $mapping->person?->update([
                    'nama' => $data['nama'],
                    'jenis_kelamin' => $data['jenis_kelamin'] === 'Perempuan' ? 'P' : 'L',
                    'desa_id' => $data['desa_id'],
                    'kelompok_id' => $data['kelompok_id'] ?? null,
                ]);
            }

            return $peserta->refresh();
        });
    }

    public function generateAttendanceCode(): string
    {
        do {
            $code = 'KJA-'.Str::upper(Str::random(8));
        } while (Participation::where('attendance_code', $code)->exists() || peserta::where('attendance_code', $code)->exists());

        return $code;
    }

    private function nextParticipantNumber(int $eventId, ?string $jenisKelamin = null): string
    {
        return PlacementService::generateParticipantNumber($eventId, $jenisKelamin);
    }

    private function ensureUniqueForEvent(int $eventId, string $participantNumber): void
    {
        if (Participation::where('event_id', $eventId)->where('participant_number', $participantNumber)->exists()) {
            throw ValidationException::withMessages([
                'participant_number' => 'Participant number already exists for this event.',
            ]);
        }
    }

    private function ensureUniqueAttendanceCode(string $attendanceCode): void
    {
        if (Participation::where('attendance_code', $attendanceCode)->exists() || peserta::where('attendance_code', $attendanceCode)->exists()) {
            throw ValidationException::withMessages([
                'attendance_code' => 'Attendance code already exists.',
            ]);
        }
    }

    private function activeEvent(): Event
    {
        $context = app(ActiveEventContext::class);

        return $context->current() ?? throw new \RuntimeException('No active event available.');
    }
}
