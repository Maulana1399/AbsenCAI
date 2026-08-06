<?php

namespace App\Services\Import\Adapters;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\Contracts\ImportDefinitionMetadata;
use App\Services\Import\Contracts\ImportTemplate;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Exceptions\ImportDefinitionNotFoundException;
use App\Services\Import\Exceptions\ImportException;
use App\Services\Import\Pipeline\ImportCoordinator;
use App\Services\Import\Registry\ImportRegistry;
use App\Services\Import\Results\ImportPipelineResult;

/**
 * Reusable adapter between legacy entry points (controller, wizard) and the
 * Import Framework.
 *
 * The caller never touches the pipeline — it only asks the adapter to run a
 * definition by key. The same adapter serves every module (desa, kelompok, …)
 * and passes wizard parameters (e.g. desa_id) through the ImportContext.
 */
final class ImportAdapter
{
    public function __construct(
        private readonly ImportRegistry $registry,
        private readonly ImportCoordinator $coordinator,
    ) {}

    /**
     * Run the pipeline in preview mode (no commit). Returns parsed/validated
     * rows plus the validation summary.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function preview(string $definitionKey, mixed $file, ?ImportContext $context = null, array $parameters = []): ImportPipelineResult
    {
        return $this->coordinator->execute(
            $definitionKey,
            $context ?? $this->makeContext($definitionKey, $file, 'preview', $parameters),
            $file,
        );
    }

    /**
     * Run the pipeline in execute mode — parse, validate, detect duplicates
     * and commit.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function commit(string $definitionKey, mixed $file, ?ImportContext $context = null, array $parameters = []): ImportPipelineResult
    {
        return $this->coordinator->execute(
            $definitionKey,
            $context ?? $this->makeContext($definitionKey, $file, 'execute', $parameters),
            $file,
        );
    }

    public function definition(string $definitionKey): ImportDefinition
    {
        $definition = $this->registry->resolve($definitionKey);

        if ($definition === null) {
            throw new ImportDefinitionNotFoundException($definitionKey);
        }

        return $definition;
    }

    public function metadata(string $definitionKey): ImportDefinitionMetadata
    {
        $definition = $this->definition($definitionKey);

        if (! $definition instanceof ImportDefinitionMetadata) {
            throw new ImportException("Definition '{$definitionKey}' tidak menyediakan metadata.");
        }

        return $definition;
    }

    public function template(string $definitionKey): ImportTemplate
    {
        $definition = $this->metadata($definitionKey);

        return $definition->template();
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function makeContext(string $definitionKey, mixed $file, string $mode, array $parameters = []): ImportContext
    {
        $fileName = is_object($file) && method_exists($file, 'getClientOriginalName')
            ? $file->getClientOriginalName()
            : null;

        return new ImportContext(
            type: $definitionKey,
            userId: auth()->id(),
            fileName: $fileName,
            source: 'adapter',
            mode: $mode,
            options: ['file' => $file, 'parameters' => $parameters],
            definitionKey: $definitionKey,
        );
    }
}
