<?php

namespace App\Services\Import\Contracts;

use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportPreview;

interface ImportActivityLogger
{
    public function logPreview(ImportContext $context, ImportPreview $preview): void;

    public function logCommit(ImportContext $context, ImportCommit $commit): void;
}
