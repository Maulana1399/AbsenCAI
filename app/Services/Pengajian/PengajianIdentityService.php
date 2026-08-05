<?php

namespace App\Services\Pengajian;

use App\Models\DesaAccessGrant;
use App\Models\EventAttendance;
use App\Models\IdentityCorrectionRequest;
use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PengajianIdentityService
{
    public const MIN_QUERY_LENGTH = 3;

    public const MAX_RESULTS = 20;

    private const BIRTH_DATE_FORMAT_MASKED = 'd M';

    public function searchPersons(DesaAccessGrant $grant, string $query, bool $includeIdentifiers = false): array
    {
        if ($grant->desa_id === null) {
            throw new \RuntimeException('Grant tidak memiliki desa scope.');
        }

        $trimmed = trim($query);

        if (mb_strlen($trimmed) < self::MIN_QUERY_LENGTH) {
            return [];
        }

        $eventId = $grant->event_id;

        $persons = Person::query()
            ->where('people.desa_id', $grant->desa_id)
            ->where(function (Builder $q) use ($trimmed, $includeIdentifiers) {
                $q->where('people.nama', 'like', '%'.$trimmed.'%');
                if ($includeIdentifiers) {
                    $q->orWhere('participations.participant_number', 'like', '%'.$trimmed.'%');
                }
            })
            ->leftJoin('participations', function ($join) use ($eventId) {
                $join->on('participations.person_id', '=', 'people.id')
                    ->where('participations.event_id', '=', $eventId);
            })
            ->leftJoin('event_attendances', 'event_attendances.participation_id', '=', 'participations.id')
            ->leftJoin('kelompoks', 'kelompoks.id', '=', 'people.kelompok_id')
            ->orderBy('people.nama')
            ->limit(self::MAX_RESULTS)
            ->get([
                'people.id',
                'people.nama',
                'people.tanggal_lahir',
                'people.desa_id',
                'participations.participant_number',
                'event_attendances.id as attendance_id',
                'event_attendances.status as attendance_status',
                'event_attendances.method as attendance_method',
                'event_attendances.attended_at',
                'kelompoks.kelompok_asal',
            ]);

        return $persons->map(fn ($row) => $this->toSearchResult($row, $includeIdentifiers))->all();
    }

    public function searchPersonsForOperator(DesaAccessGrant $grant, string $query): array
    {
        return $this->searchPersons($grant, $query, includeIdentifiers: true);
    }

    public function findPersonInDesa(int $personId, int $desaId): ?Person
    {
        return Person::query()
            ->where('id', $personId)
            ->where('desa_id', $desaId)
            ->first();
    }

    public function verifyBirthDate(Person $person, string $birthDate): bool
    {
        if ($person->tanggal_lahir === null) {
            return false;
        }

        return $person->tanggal_lahir->format('Y-m-d') === $birthDate;
    }

    public function submitCorrection(
        Person $person,
        array $data,
        ?int $eventId = null,
        ?int $desaId = null,
    ): IdentityCorrectionRequest {
        if ($person->desa_id === null) {
            throw new \RuntimeException('Person tidak memiliki desa assignment.');
        }

        if ($desaId !== null && $person->desa_id !== $desaId) {
            throw new \RuntimeException('Person tidak terdaftar di desa yang sesuai.');
        }

        $existing = IdentityCorrectionRequest::query()
            ->where('person_id', $person->id)
            ->where('status', IdentityCorrectionRequest::STATUS_PENDING)
            ->exists();

        if ($existing) {
            throw new \RuntimeException('Sudah ada permintaan koreksi yang pending untuk person ini.');
        }

        $metadata = [
            'current_name' => $person->nama,
            'current_birth_date' => $person->tanggal_lahir?->format('Y-m-d'),
            'current_desa_id' => $person->desa_id,
        ];

        return IdentityCorrectionRequest::create([
            'person_id' => $person->id,
            'event_id' => $eventId,
            'desa_id' => $desaId,
            'requested_name' => $data['requested_name'] ?? null,
            'requested_birth_date' => $data['requested_birth_date'] ?? null,
            'requested_desa_id' => $data['requested_desa_id'] ?? null,
            'reason' => $data['reason'] ?? null,
            'status' => IdentityCorrectionRequest::STATUS_PENDING,
            'submitted_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    private function toSearchResult($row, bool $includeIdentifiers): array
    {
        $status = $row->attendance_status ?? null;
        $hadir = $status === EventAttendance::STATUS_HADIR;
        $izin = $status === EventAttendance::STATUS_IZIN;
        $method = ($hadir || $izin) ? $row->attendance_method : null;
        $attendedAt = ($hadir || $izin) && $row->attended_at ? Carbon::parse($row->attended_at)->format('d M Y H:i') : null;

        $result = [
            'id' => $row->id,
            'nama' => $row->nama,
            'hadir' => $hadir,
            'izin' => $izin,
            'attended_at' => $attendedAt,
            'method' => $method,
            'birth_date_masked' => $row->tanggal_lahir?->format(self::BIRTH_DATE_FORMAT_MASKED),
            'has_birth_date' => $row->tanggal_lahir !== null,
        ];

        if ($includeIdentifiers) {
            $result['participant_number'] = $row->participant_number;
            $result['kelompok'] = $row->kelompok_asal;
        }

        return $result;
    }
}
