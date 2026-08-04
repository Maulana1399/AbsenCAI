<?php

namespace App\Services\Import\Adapters\Regu;

use App\Imports\ReguImport;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use Maatwebsite\Excel\Facades\Excel;

final class ReguImportCommitter implements ImportCommitter
{
    public function commit(ImportContext $context): ImportCommit
    {
        if (is_object($context->options['file'] ?? null)) {
            Excel::import(new ReguImport, $context->options['file']);
        }

        return new ImportCommit($context, new ImportSummary());
    }
}
