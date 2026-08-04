<?php

namespace App\Services\Import\DTO;

final readonly class ImportContext
{
    public function __construct(
        public string $type,
        public ?int $eventId = null,
        public ?int $userId = null,
        public ?string $fileName = null,
        public string $source = 'unknown',
        public string $mode = 'preview',
        public array $options = [],
        public ?string $definitionKey = null,
        public ?string $transactionId = null,
    ) {
    }
}
