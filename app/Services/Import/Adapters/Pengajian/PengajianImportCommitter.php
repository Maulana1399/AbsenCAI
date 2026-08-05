<?php

namespace App\Services\Import\Adapters\Pengajian;

use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use App\Services\Pengajian\PengajianImportService;

final class PengajianImportCommitter implements ImportCommitter
{
    public function __construct(
        private readonly PengajianImportService $service,
    ) {}

    public function commit(ImportContext $context): ImportCommit
    {
        $eventId = $context->eventId ?? 0;
        $result = $this->service->import($context->options['rows'] ?? [], $eventId);

        return new ImportCommit(
            $context,
            new ImportSummary(
                createdRows: $result['created_participations'] ?? 0,
                skippedRows: $result['skipped_duplicates'] ?? 0,
                errors: $result['errors'] ?? [],
            ),
        );
    }
}
