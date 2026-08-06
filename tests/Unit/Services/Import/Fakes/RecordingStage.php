<?php

namespace Tests\Unit\Services\Import\Fakes;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\Contracts\ImportPipelineStage;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Pipeline\ImportPipelineState;

class RecordingStage implements ImportPipelineStage
{
    public static array $executed = [];

    public static array $skipped = [];

    public static array $payloads = [];

    public static function reset(): void
    {
        self::$executed = [];
        self::$skipped = [];
        self::$payloads = [];
    }

    public function __construct(
        private readonly string $name,
        private readonly bool $supported = true,
        private readonly ?\Closure $transform = null,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function supports(ImportContext $context, ImportDefinition $definition): bool
    {
        return $this->supported;
    }

    public function handle(
        mixed $payload,
        ImportContext $context,
        ImportDefinition $definition,
        ImportPipelineState $state,
    ): mixed {
        self::$payloads[$this->name] = $payload;

        if (! $this->supported) {
            self::$skipped[] = $this->name;

            return $payload;
        }

        self::$executed[] = $this->name;

        return $this->transform !== null
            ? ($this->transform)($payload)
            : $payload;
    }
}
