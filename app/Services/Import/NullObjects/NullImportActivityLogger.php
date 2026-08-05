<?php

namespace App\Services\Import\NullObjects;

use App\Services\Import\Contracts\ImportActivityLogger;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportPreview;

final class NullImportActivityLogger implements ImportActivityLogger
{
    public function logPreview(ImportContext $context, ImportPreview $preview): void {}

    public function logCommit(ImportContext $context, ImportCommit $commit): void {}
}
