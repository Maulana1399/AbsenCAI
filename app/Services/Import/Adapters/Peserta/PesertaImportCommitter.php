<?php

namespace App\Services\Import\Adapters\Peserta;

use App\Imports\PesertaImport;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use Maatwebsite\Excel\Facades\Excel;

final class PesertaImportCommitter implements ImportCommitter
{
    public function commit(ImportContext $context): ImportCommit
    {
        if (is_object($context->options['file'] ?? null)) {
            Excel::import(new PesertaImport, $context->options['file']);
        }

        return new ImportCommit($context, new ImportSummary());
    }
}
