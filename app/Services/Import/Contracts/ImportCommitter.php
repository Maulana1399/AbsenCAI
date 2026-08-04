<?php

namespace App\Services\Import\Contracts;

use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;

interface ImportCommitter
{
    public function commit(ImportContext $context): ImportCommit;
}
