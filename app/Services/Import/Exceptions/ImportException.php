<?php

namespace App\Services\Import\Exceptions;

/**
 * Base exception for every Import Framework error. Domain code should throw
 * one of the typed subclasses instead of a generic RuntimeException.
 */
class ImportException extends \RuntimeException {}
