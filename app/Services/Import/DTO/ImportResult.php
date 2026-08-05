<?php

namespace App\Services\Import\DTO;

use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportPreview;

final readonly class ImportResult
{
    public function __construct(
        public ImportContext $context,
        public ?ImportPreview $preview = null,
        public ?ImportCommit $commit = null,
        public string $status = 'pending',
    ) {}
}
