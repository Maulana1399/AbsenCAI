<?php

namespace App\Services\Import\DTO;

use App\Models\Event;
use App\Models\User;

/**
 * Immutable description of an import run.
 *
 * Carries the input (type, event, user, file, version, options) and routing
 * metadata (mode, definitionKey, transactionId). Outputs produced during
 * execution (rows, preview, errors, warnings, summary, statistics) are carried
 * by the mutable ImportPipelineState so this object never changes.
 */
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
        public ?Event $event = null,
        public ?User $user = null,
        public ?string $version = null,
    ) {}
}
