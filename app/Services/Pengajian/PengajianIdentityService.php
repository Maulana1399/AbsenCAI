<?php

namespace App\Services\Pengajian;

use App\Models\DesaAccessGrant;
use App\Models\IdentityCorrectionRequest;
use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;

class PengajianIdentityService
{
    public const MIN_QUERY_LENGTH = 3;
    public const MAX_RESULTS = 20;

    private const BIRTH_DATE_FORMAT_MASKED = 'd M';

    public function searchPersons(DesaAccessGrant $grant, string $query): array
    {
        if ($grant->desa_id === null) {
            throw new \RuntimeException('Grant tidak memiliki desa scope.');
        }

        $trimmed = trim($query);

        if (mb_strlen($trimmed) < self::MIN_QUERY_LENGTH) {
            return [];
        }

        $persons = Person::query()
            ->where('desa_id', $grant->desa_id)
            ->where(function (Builder $q) use ($trimmed) {
                $q->where('nama', 'like', '%'.$trimmed.'%');
            })
            ->orderBy('nama')
            ->limit(self::MAX_RESULTS)
            ->get(['id', 'nama', 'tanggal_lahir', 'desa_id']);

        return $persons->map(fn (Person $person) => $this->toSearchResult($person))->all();
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

    private function toSearchResult(Person $person): array
    {
        return [
            'id' => $person->id,
            'nama' => $person->nama,
            'birth_date_masked' => $person->tanggal_lahir?->format(self::BIRTH_DATE_FORMAT_MASKED),
            'has_birth_date' => $person->tanggal_lahir !== null,
        ];
    }
}
