<?php

namespace App\Services\Import\Adapters\Regu;

use App\Services\Import\Contracts\ImportActivityLogger;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportPreview;

final class ReguImportActivityLogger implements ImportActivityLogger
{
    public function logPreview(ImportContext $context, ImportPreview $preview): void {}

    public function logCommit(ImportContext $context, ImportCommit $commit): void {}
}
