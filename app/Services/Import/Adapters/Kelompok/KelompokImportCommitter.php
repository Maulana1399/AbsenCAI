<?php

namespace App\Services\Import\Adapters\Kelompok;

use App\Imports\KelompokImport;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use Maatwebsite\Excel\Facades\Excel;

final class KelompokImportCommitter implements ImportCommitter
{
    public function commit(ImportContext $context): ImportCommit
    {
        if (is_object($context->options['file'] ?? null)) {
            Excel::import(new KelompokImport, $context->options['file']);
        }

        return new ImportCommit($context, new ImportSummary());
    }
}
