<?php

namespace App\Services\Registration;

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
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
                $legacyParticipantNumber = $this->resolveLegacyParticipantNumber($participantNumber);

                $this->ensureUniqueForEvent($event->id, $participantNumber);
                $this->ensureUniqueAttendanceCode($attendanceCode);

                // Look up existing Person by canonical identity (nama + desa + kelompok)
                $person = Person::where('nama', $data['nama'])
                    ->where('desa_id', $data['desa_id'])
                    ->where('kelompok_id', $data['kelompok_id'])
                    ->first();

                if ($person) {
                    $existingParticipation = Participation::where('person_id', $person->id)
                        ->where('event_id', $event->id)
                        ->first();

                    if ($existingParticipation) {
                        throw ValidationException::withMessages([
                            'nama' => 'Person ini sudah terdaftar pada event ini.',
                        ]);
                    }

                    $legacyPeserta = peserta::where('nama', $data['nama'])
                        ->where('desa_id', $data['desa_id'])
                        ->where('kelompok_id', $data['kelompok_id'])
                        ->first();

                    if (! $legacyPeserta) {
                        throw ValidationException::withMessages([
                            'nama' => 'Legacy peserta compatibility record tidak ditemukan untuk identity ini.',
                        ]);
                    }

                    $nip = $legacyPeserta->nip ?? $person->nip;

                    $reguId = $data['regu_id'] ?? $legacyPeserta?->regu_id;

                    $participation = Participation::create([
                    'person_id' => $person->id,
                    'event_id' => $event->id,
                    'participant_number' => $participantNumber,
                    'attendance_code' => $attendanceCode,
                    'jenis_peserta' => $data['jenis_peserta'],
                    'regu_id' => $reguId,
                ]);

                    // Dual-write to legacy pesertas.regu_id stopped per Sprint 7

                    LegacyParticipationMapping::create([
                        'peserta_id' => $legacyPeserta->id,
                        'person_id' => $person->id,
                        'participation_id' => $participation->id,
                        'event_id' => $event->id,
                        'migrated_at' => now(),
                    ]);

                    return $legacyPeserta->refresh();
                }

                // CASE A: New Person — create full set
                $peserta = peserta::create([
                    'nama' => $data['nama'],
                    'nip' => $data['nip'],
                    'participant_number' => $legacyParticipantNumber,
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
                    'regu_id' => $data['regu_id'],
                ]);

                LegacyPesertaMapping::create([
                    'peserta_id' => $peserta->id,
                    'person_id' => $person->id,
                    'legacy_nip' => $peserta->nip,
                    'legacy_participant_number' => $legacyParticipantNumber,
                    'legacy_attendance_code' => $attendanceCode,
                    'migrated_at' => now(),
                ]);

                LegacyParticipationMapping::create([
                    'peserta_id' => $peserta->id,
                    'person_id' => $person->id,
                    'participation_id' => $participation->id,
                    'event_id' => $event->id,
                    'migrated_at' => now(),
                ]);

                return $peserta->refresh();
            });
        } catch (QueryException $exception) {
            $message = $exception->getMessage();

            if (str_contains($message, 'UNIQUE') && str_contains($message, 'pesertas')) {
                if (str_contains($message, 'pesertas.nama') || str_contains($message, 'nama_desa_id_kelompok_id')) {
                    throw ValidationException::withMessages([
                        'nama' => 'Peserta dengan nama, desa, dan kelompok ini sudah terdaftar. Schema constraint (nama+desa+kelompok) belum dimigrasi untuk multi-event.',
                    ]);
                }

                throw ValidationException::withMessages([
                    'nama' => 'Peserta dengan data ini sudah terdaftar (UNIQUE constraint di tabel pesertas).',
                ]);
            }

            if (str_contains($message, 'UNIQUE') && str_contains($message, 'people')) {
                throw ValidationException::withMessages([
                    'nama' => 'Data person sudah terdaftar.',
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
            $pesertaMapping = $peserta->legacyPesertaMapping;
            $participationMapping = LegacyParticipationMapping::with('participation')
                ->where('peserta_id', $peserta->id)
                ->first();

            $peserta->update([
                'nama' => $data['nama'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'jenis_peserta' => $data['jenis_peserta'],
                'desa_id' => $data['desa_id'],
                'kelompok_id' => $data['kelompok_id'],
            ]);

            if ($participationMapping?->participation !== null) {
                $participationMapping->participation->update([
                    'jenis_peserta' => $data['jenis_peserta'],
                    'regu_id' => $data['regu_id'],
                ]);
            }

            if ($pesertaMapping?->person !== null) {
                $pesertaMapping->person->update([
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

    private function resolveLegacyParticipantNumber(string $participantNumber): string
    {
        if (! peserta::where('participant_number', $participantNumber)->exists()) {
            return $participantNumber;
        }

        $next = ((int) (peserta::max('participant_number') ?? 0)) + 1;

        do {
            $candidate = 'KL' . str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            $next++;
        } while (peserta::where('participant_number', $candidate)->exists());

        return $candidate;
    }

    private function activeEvent(): Event
    {
        $context = app(ActiveEventContext::class);

        return $context->current() ?? throw new \RuntimeException('No active event available.');
    }
}
