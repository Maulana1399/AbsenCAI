<?php

namespace App\Services\Import\Exceptions;

final class ImportParseException extends ImportException
{
    public function __construct(string $message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
