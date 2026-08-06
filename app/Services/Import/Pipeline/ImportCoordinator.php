<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Exceptions\ImportDefinitionNotFoundException;
use App\Services\Import\Exceptions\ImportVersionNotSupportedException;
use App\Services\Import\Registry\ImportRegistry;
use App\Services\Import\Results\ImportPipelineResult;
use App\Services\Import\Support\ImportVersion;

/**
 * Entry point for running an import.
 *
 * Resolves the definition from the registry, guards template/version
 * compatibility, then delegates to the pipeline. Resolvable through the
 * container (ImportServiceProvider) or constructible manually with the same
 * constructor shape the legacy callers use.
 */
final class ImportCoordinator
{
    public function __construct(
        private readonly ImportRegistry $registry,
        private readonly ImportPipeline $pipeline,
    ) {}

    public function execute(string $definitionKey, ImportContext $context, mixed $source): ImportPipelineResult
    {
        $definition = $this->registry->resolve($definitionKey);

        if ($definition === null) {
            throw new ImportDefinitionNotFoundException($definitionKey);
        }

        $this->assertSupportedVersion($definition, $context);

        return $this->pipeline->run($definition, $context, $source);
    }

    private function assertSupportedVersion(ImportDefinition $definition, ImportContext $context): void
    {
        if ($context->version === null) {
            return;
        }

        $versions = new ImportVersion(
            current: $definition->currentVersion(),
            minimum: $definition->minimumVersion(),
            supported: $definition->supportedVersion(),
        );

        if (! $versions->accepts($context->version)) {
            throw new ImportVersionNotSupportedException($context->version, $definition);
        }
    }
}
