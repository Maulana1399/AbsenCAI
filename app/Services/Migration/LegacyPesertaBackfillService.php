<?php

namespace App\Services\Migration;

use App\Models\Event;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LegacyPesertaBackfillService
{
    public function execute(Event $event, bool $dryRun = true, ?string $batchId = null): BackfillReport
    {
        $report = new BackfillReport($event, $dryRun, $batchId);
        $report->totalPeserta = peserta::query()->count();

        peserta::query()->orderBy('id')->chunk(100, function ($pesertas) use ($event, $dryRun, $batchId, $report) {
            foreach ($pesertas as $peserta) {
                $item = $this->processPeserta($peserta, $event, $dryRun, $batchId);
                $report->addItem($item);
            }
        });

        return $report;
    }

    private function processPeserta(peserta $peserta, Event $event, bool $dryRun, ?string $batchId): BackfillReportItem
    {
        $item = new BackfillReportItem($peserta);

        $mapping = LegacyPesertaMapping::where('peserta_id', $peserta->id)->first();

        if ($mapping) {
            $this->validateExistingMapping($mapping, $peserta, $event, $item);
            return $item;
        }

        $person = Person::where('nip', $peserta->nip)->first();

        $personResolution = $this->resolvePerson($peserta, $person);

        if ($personResolution['outcome'] === 'CONFLICT' || $personResolution['outcome'] === 'REVIEW_REQUIRED') {
            $item->outcome = $personResolution['outcome'];
            $item->messages = $personResolution['messages'];
            return $item;
        }

        $resolvedPerson = $personResolution['person'];

        $participation = $resolvedPerson
            ? Participation::where('person_id', $resolvedPerson->id)
                ->where('event_id', $event->id)
                ->first()
            : null;

        $participationResolution = $this->resolveParticipation($peserta, $resolvedPerson, $participation, $event);

        if ($participationResolution['outcome'] === 'CONFLICT') {
            $item->outcome = 'CONFLICT';
            $item->messages = $participationResolution['messages'];
            return $item;
        }

        $resolvedParticipation = $participationResolution['participation'];

        if ($resolvedPerson === null) {
            $item->outcome = 'CREATE_PERSON';
        } elseif ($resolvedParticipation !== null && $participationResolution['outcome'] === 'MATCHED_EXISTING') {
            $item->outcome = 'MATCHED_BY_NIP';
        } else {
            $item->outcome = 'MATCHED_BY_NIP';
        }

        if ($dryRun) {
            $item->projectedPersonData = $personResolution['data'];
            $item->projectedParticipationData = $participationResolution['data'];
            $item->projectedMappingData = $this->projectMappingData($peserta, $resolvedPerson, $resolvedParticipation, $event, $batchId);
            return $item;
        }

        try {
            DB::transaction(function () use ($peserta, $personResolution, $participationResolution, $event, $batchId, $item) {
                $person = $personResolution['person']
                    ?? Person::create($personResolution['data']);

                if (!$personResolution['person']) {
                    $item->peopleCreated = 1;
                }

                $participation = $participationResolution['participation']
                    ?? Participation::create(array_merge(
                        $participationResolution['data'],
                        ['person_id' => $person->id]
                    ));

                if (!$participationResolution['participation']) {
                    $item->participationsCreated = 1;
                }

                LegacyPesertaMapping::create([
                    'peserta_id' => $peserta->id,
                    'person_id' => $person->id,
                    'participation_id' => $participation->id,
                    'event_id' => $event->id,
                    'backfill_batch_id' => $batchId,
                    'legacy_nip' => $peserta->nip,
                    'legacy_participant_number' => $peserta->participant_number,
                    'legacy_attendance_code' => $peserta->attendance_code,
                    'migrated_at' => now(),
                ]);

                $item->mappingsCreated = 1;
            });
        } catch (QueryException $e) {
            $item->outcome = 'ERROR';
            $item->messages[] = 'Database error: ' . $e->getMessage();
        }

        return $item;
    }

    private function validateExistingMapping(LegacyPesertaMapping $mapping, peserta $peserta, Event $targetEvent, BackfillReportItem $item): void
    {
        $person = $mapping->person;
        $participation = $mapping->participation;
        $event = $mapping->event;

        if (!$person || !$participation || !$event) {
            $item->outcome = 'BROKEN_MAPPING';
            $item->messages[] = 'Mapping references non-existent record';
            return;
        }

        if ($event->id !== $targetEvent->id) {
            $item->outcome = 'BROKEN_MAPPING';
            $item->messages[] = 'Mapping event does not match target event';
            return;
        }

        if ($participation->person_id !== $person->id) {
            $item->outcome = 'BROKEN_MAPPING';
            $item->messages[] = 'Participation does not belong to mapped person';
            return;
        }

        if ($participation->event_id !== $event->id) {
            $item->outcome = 'BROKEN_MAPPING';
            $item->messages[] = 'Participation does not belong to mapped event';
            return;
        }

        $drifts = [];
        if ((string) $mapping->legacy_nip !== (string) $peserta->nip) {
            $drifts[] = "legacy_nip changed from {$mapping->legacy_nip} to {$peserta->nip}";
        }
        if ((string) $mapping->legacy_participant_number !== (string) $peserta->participant_number) {
            $drifts[] = "legacy_participant_number changed from {$mapping->legacy_participant_number} to {$peserta->participant_number}";
        }
        if ((string) $mapping->legacy_attendance_code !== (string) $peserta->attendance_code) {
            $drifts[] = "legacy_attendance_code changed from {$mapping->legacy_attendance_code} to {$peserta->attendance_code}";
        }

        if (!empty($drifts)) {
            $item->outcome = 'DRIFT_DETECTED';
            $item->messages = $drifts;
            return;
        }

        $item->outcome = 'ALREADY_MAPPED';
    }

    private function resolvePerson(peserta $peserta, ?Person $person): array
    {
        if ($person === null) {
            $canonicalGender = $this->normalizeGender($peserta->jenis_kelamin);

            return [
                'outcome' => 'CREATE_PERSON',
                'person' => null,
                'messages' => [],
                'data' => [
                    'nama' => $peserta->nama,
                    'jenis_kelamin' => $canonicalGender,
                    'desa_id' => $peserta->desa_id,
                    'nip' => $peserta->nip,
                ],
            ];
        }

        $messages = [];
        $hasConflict = false;
        $hasReview = false;

        $normalizedPesertaName = $this->normalizeName($peserta->nama);
        $normalizedPersonName = $this->normalizeName($person->nama);

        if ($normalizedPesertaName !== $normalizedPersonName) {
            $messages[] = "NIP {$peserta->nip}: name differs — peserta '{$peserta->nama}' vs person '{$person->nama}'";
            $hasReview = true;
        }

        $canonicalPesertaGender = $this->normalizeGender($peserta->jenis_kelamin);
        if ($canonicalPesertaGender !== null && $person->jenis_kelamin !== null && $canonicalPesertaGender !== $person->jenis_kelamin) {
            $messages[] = "NIP {$peserta->nip}: gender mismatch — peserta '{$peserta->jenis_kelamin}' vs person '{$person->jenis_kelamin}'";
            $hasConflict = true;
        }

        if ($peserta->desa_id !== null && $person->desa_id !== null && $peserta->desa_id !== $person->desa_id) {
            $messages[] = "NIP {$peserta->nip}: desa differs — peserta desa_id={$peserta->desa_id} vs person desa_id={$person->desa_id}";
            $hasReview = true;
        }

        if ($hasConflict) {
            return ['outcome' => 'CONFLICT', 'person' => $person, 'messages' => $messages, 'data' => null];
        }

        if ($hasReview) {
            return ['outcome' => 'REVIEW_REQUIRED', 'person' => $person, 'messages' => $messages, 'data' => null];
        }

        return ['outcome' => 'MATCHED_BY_NIP', 'person' => $person, 'messages' => [], 'data' => null];
    }

    private function resolveParticipation(peserta $peserta, ?Person $person, ?Participation $participation, Event $event): array
    {
        if ($participation) {
            $conflicts = [];

            if ((string) $participation->participant_number !== (string) $peserta->participant_number) {
                $conflicts[] = "participant_number differs — participation '{$participation->participant_number}' vs legacy '{$peserta->participant_number}'";
            }

            if ((string) $participation->attendance_code !== (string) $peserta->attendance_code) {
                $conflicts[] = "attendance_code differs — participation '{$participation->attendance_code}' vs legacy '{$peserta->attendance_code}'";
            }

            if (!empty($conflicts)) {
                return ['outcome' => 'CONFLICT', 'participation' => $participation, 'messages' => $conflicts, 'data' => null];
            }

            return ['outcome' => 'MATCHED_EXISTING', 'participation' => $participation, 'messages' => [], 'data' => null];
        }

        $conflicts = [];

        if ($peserta->participant_number !== null) {
            $existing = Participation::where('event_id', $event->id)
                ->where('participant_number', $peserta->participant_number)
                ->first();
            if ($existing) {
                $conflicts[] = "participant_number '{$peserta->participant_number}' already exists in event {$event->slug}";
            }
        }

        if ($peserta->attendance_code !== null) {
            $existing = Participation::where('attendance_code', $peserta->attendance_code)->first();
            if ($existing) {
                $conflicts[] = "attendance_code '{$peserta->attendance_code}' is globally unique and already in use";
            }
        }

        if (!empty($conflicts)) {
            return ['outcome' => 'CONFLICT', 'participation' => null, 'messages' => $conflicts, 'data' => null];
        }

        $data = [
            'person_id' => $person?->id,
            'event_id' => $event->id,
            'participant_number' => $peserta->participant_number,
            'attendance_code' => $peserta->attendance_code,
            'jenis_peserta' => $peserta->jenis_peserta ?? 'Wajib',
        ];

        return ['outcome' => 'CREATE_PARTICIPATION', 'participation' => null, 'messages' => [], 'data' => $data];
    }

    private function projectMappingData(peserta $peserta, ?Person $person, ?Participation $participation, Event $event, ?string $batchId): array
    {
        return [
            'peserta_id' => $peserta->id,
            'person_id' => $person?->id,
            'participation_id' => $participation?->id,
            'event_id' => $event->id,
            'backfill_batch_id' => $batchId,
            'legacy_nip' => $peserta->nip,
            'legacy_participant_number' => $peserta->participant_number,
            'legacy_attendance_code' => $peserta->attendance_code,
            'migrated_at' => now(),
        ];
    }

    public function normalizeGender(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $normalized = strtolower(str_replace([' ', '-'], '', trim($value)));

        return match ($normalized) {
            'l', 'lakilaki' => 'L',
            'p', 'perempuan' => 'P',
            default => null,
        };
    }

    public function normalizeName(string $name): string
    {
        return strtolower(trim($name));
    }
}
