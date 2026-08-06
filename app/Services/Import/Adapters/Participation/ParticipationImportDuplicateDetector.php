<?php

namespace App\Services\Import\Adapters\Participation;

use App\Models\Participation;
use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportWarning;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\Results\ImportSummary;
use App\Services\Registration\ManualParticipantRegistrationService;

final class ParticipationImportDuplicateDetector implements ImportDuplicateDetector
{
    public function __construct(
        private readonly ManualParticipantRegistrationService $registrationService,
    ) {}

    /**
     * Duplicate = existing Participation for the resolved Person in the target
     * event. Person resolution reuses the canonical service; ambiguous person
     * identities surface as warnings.
     *
     * @param  array<int, NormalizedImportRow>  $rows
     */
    public function detect(array $rows, ImportContext $context): ImportSummary
    {
        $eventId = (int) ($context->options['parameters']['event_id'] ?? 0);
        $seen = [];
        $duplicateCount = 0;
        $warnings = [];

        foreach ($rows as $row) {
            $key = $row->duplicateKey;

            if ($key !== null && $key !== '') {
                if (isset($seen[$key])) {
                    $duplicateCount++;

                    continue;
                }

                $seen[$key] = true;
            }

            $resolution = $this->registrationService->resolvePerson(
                nama: $row->data['nama'] ?? '',
                desaId: (int) ($row->data['desa_id'] ?? 0),
                tanggalLahir: $row->data['tanggal_lahir'] ?? null,
            );

            if ($resolution['status'] === 'exact') {
                $exists = Participation::where('person_id', $resolution['person']->id)
                    ->where('event_id', $eventId)
                    ->exists();

                if ($exists) {
                    $duplicateCount++;
                }

                continue;
            }

            if ($resolution['status'] === 'ambiguous') {
                $warnings[] = new ImportWarning(
                    rowNumber: $row->rowNumber,
                    field: 'nama',
                    message: 'Terdapat kandidat Person serupa. Verifikasi identitas manual.',
                );

                continue;
            }
        }

        return new ImportSummary(
            totalRows: count($rows),
            validRows: count($rows) - $duplicateCount,
            duplicateRows: $duplicateCount,
            warnings: $warnings,
        );
    }
}
