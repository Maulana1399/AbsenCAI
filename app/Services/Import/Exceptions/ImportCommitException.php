<?php

namespace App\Services\Import\Exceptions;

/**
 * Reserved for atomic/batch commit failures. The default commit stage lets
 * business exceptions (e.g. Maatwebsite\Excel ValidationException) propagate
 * unchanged so legacy controllers keep their current behavior; this typed
 * exception is available for framework-internal commit failures.
 */
final class ImportCommitException extends ImportException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
