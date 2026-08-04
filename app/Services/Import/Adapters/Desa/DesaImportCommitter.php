<?php

namespace App\Services\Import\Adapters\Desa;

use App\Imports\DesaImport;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use Maatwebsite\Excel\Facades\Excel;

final class DesaImportCommitter implements ImportCommitter
{
    public function commit(ImportContext $context): ImportCommit
    {
        if (is_object($context->options['file'] ?? null)) {
            Excel::import(new DesaImport, $context->options['file']);
        }

        return new ImportCommit($context, new ImportSummary());
    }
}
