<?php

namespace App\Services\Pengajian;

use App\Models\DesaAccessGrant;
use App\Models\IdentityCorrectionRequest;
use App\Models\LegacyPesertaMapping;
use App\Models\Person;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class IdentityCorrectionService
{
    private const ALLOWED_FIELDS = ['requested_name', 'requested_birth_date'];

    public function submitFromPublicContext(
        Person $person,
        DesaAccessGrant $grant,
        array $proposedChanges,
    ): IdentityCorrectionRequest {
        if (! $grant->isNonceValid()) {
            throw new \RuntimeException('Sesi QR tidak valid atau sudah kedaluwarsa.');
        }

        if ($person->desa_id === null) {
            throw new \RuntimeException('Person tidak memiliki desa assignment.');
        }

        if ((int) $person->desa_id !== (int) $grant->desa_id) {
            throw new \RuntimeException('Person tidak terdaftar di desa ini.');
        }

        $filtered = $this->filterAllowedFields($proposedChanges);

        if (empty($filtered)) {
            throw new \RuntimeException('Tidak ada field yang valid untuk dikoreksi.');
        }

        $this->ensureHasChanges($person, $filtered);

        $existing = IdentityCorrectionRequest::query()
            ->where('person_id', $person->id)
            ->where('status', IdentityCorrectionRequest::STATUS_PENDING)
            ->where(function ($q) use ($filtered) {
                foreach ($filtered as $field => $value) {
                    $q->orWhere($field, $value);
                }
            })
            ->exists();

        if ($existing) {
            throw new \RuntimeException('Permintaan koreksi yang identik sudah menunggu review.');
        }

        $metadata = [
            'current_name' => $person->nama,
            'current_birth_date' => $person->tanggal_lahir?->format('Y-m-d'),
            'current_desa_id' => $person->desa_id,
        ];

        return IdentityCorrectionRequest::create([
            'person_id' => $person->id,
            'event_id' => $grant->event_id,
            'desa_id' => $grant->desa_id,
            'requested_name' => $filtered['requested_name'] ?? null,
            'requested_birth_date' => $filtered['requested_birth_date'] ?? null,
            'reason' => $proposedChanges['reason'] ?? null,
            'status' => IdentityCorrectionRequest::STATUS_PENDING,
            'submitted_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    public function approve(IdentityCorrectionRequest $request, User $reviewer, ?string $operatorNotes = null): void
    {
        DB::transaction(function () use ($request, $reviewer, $operatorNotes) {
            $locked = IdentityCorrectionRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            if ($locked->status !== IdentityCorrectionRequest::STATUS_PENDING) {
                throw new \RuntimeException(
                    'Permintaan koreksi sudah '.$locked->status.'.'
                );
            }

            $person = Person::query()
                ->lockForUpdate()
                ->findOrFail($locked->person_id);

            $updates = [];

            if ($locked->requested_name !== null && $locked->requested_name !== '') {
                $updates['nama'] = $locked->requested_name;
            }

            if ($locked->requested_birth_date !== null) {
                $updates['tanggal_lahir'] = $locked->requested_birth_date;
            }

            if (! empty($updates)) {
                $person->update($updates);

                $this->syncLegacyPeserta($person, $updates);
            }

            $locked->update([
                'status' => IdentityCorrectionRequest::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'operator_notes' => $operatorNotes,
            ]);
        });
    }

    public function reject(
        IdentityCorrectionRequest $request,
        User $reviewer,
        ?string $reason = null,
        ?string $operatorNotes = null,
    ): void {
        DB::transaction(function () use ($request, $reviewer, $reason, $operatorNotes) {
            $locked = IdentityCorrectionRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            if ($locked->status !== IdentityCorrectionRequest::STATUS_PENDING) {
                throw new \RuntimeException(
                    'Permintaan koreksi sudah '.$locked->status.'.'
                );
            }

            $locked->update([
                'status' => IdentityCorrectionRequest::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'reason' => $reason ?? $locked->reason,
                'operator_notes' => $operatorNotes,
            ]);
        });
    }

    public function listPending(?int $desaId = null): array
    {
        $query = IdentityCorrectionRequest::query()
            ->with(['person', 'desa', 'event'])
            ->where('status', IdentityCorrectionRequest::STATUS_PENDING)
            ->orderBy('submitted_at', 'desc');

        if ($desaId !== null) {
            $query->where('desa_id', $desaId);
        }

        return $query->get()->all();
    }

    private function filterAllowedFields(array $data): array
    {
        $filtered = [];

        foreach (self::ALLOWED_FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];

                if ($field === 'requested_name' && (is_string($value) && trim($value) === '')) {
                    continue;
                }

                $filtered[$field] = $value;
            }
        }

        return $filtered;
    }

    private function ensureHasChanges(Person $person, array $filtered): void
    {
        $hasChange = false;

        if (isset($filtered['requested_name'])) {
            $trimmed = trim($filtered['requested_name']);
            if ($trimmed !== $person->nama) {
                $hasChange = true;
            }
        }

        if (isset($filtered['requested_birth_date'])) {
            $current = $person->tanggal_lahir?->format('Y-m-d');

            if ($filtered['requested_birth_date'] instanceof \DateTimeInterface
                || $filtered['requested_birth_date'] instanceof \Carbon\Carbon) {
                $proposed = $filtered['requested_birth_date']->format('Y-m-d');
            } else {
                $proposed = (string) $filtered['requested_birth_date'];
            }

            if ($proposed !== $current) {
                $hasChange = true;
            }
        }

        if (! $hasChange) {
            throw new \RuntimeException('Tidak ada perubahan data yang perlu dikoreksi.');
        }
    }

    private function syncLegacyPeserta(Person $person, array $updates): void
    {
        $mapping = LegacyPesertaMapping::query()
            ->where('person_id', $person->id)
            ->first();

        if ($mapping === null) {
            return;
        }

        $pesertaUpdates = [];

        if (isset($updates['nama'])) {
            $pesertaUpdates['nama'] = $updates['nama'];
        }

        if (! empty($pesertaUpdates)) {
            $mapping->peserta()->update($pesertaUpdates);
        }
    }
}
