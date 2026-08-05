<?php

namespace App\Services\Import\Results;

use App\Services\Import\DTO\ImportContext;

final readonly class ImportResult
{
    public function __construct(
        public ImportContext $context,
        public ?ImportPreview $preview = null,
        public ?ImportCommit $commit = null,
        public string $status = 'pending',
    ) {}
}
