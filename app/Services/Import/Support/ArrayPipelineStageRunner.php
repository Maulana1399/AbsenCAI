<?php

namespace App\Services\Import\Support;

use App\Services\Import\Contracts\ImportLogger;
use App\Services\Import\Contracts\ImportPipelineStage;
use App\Services\Import\Exceptions\ImportStageNotFoundException;
use App\Services\Import\Pipeline\ImportPipelineState;
use App\Services\Import\Results\ImportPipelineResult;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;

/**
 * Default PipelineStageRunner.
 *
 * Resolves each stage by name (from an explicit map, else from the container
 * via the `App\Services\Import\Pipeline\{Studly}Stage` convention), skips stages
 * that do not support the current context, and dispatches the rest in order.
 *
 * The legacy callers construct this class without arguments
 * (`new ArrayPipelineStageRunner`) — all dependencies are optional so that
 * instantiation keeps working while DI-resolved runs get the full wiring.
 */
final class ArrayPipelineStageRunner implements PipelineStageRunner
{
    /**
     * @param  array<string, ImportPipelineStage>|null  $stages
     */
    public function __construct(
        private readonly ?array $stages = null,
        private readonly ?Container $container = null,
        private readonly ?ImportLogger $logger = null,
    ) {}

    public function run(ImportPipelineState $state, array $stageOrder): ImportPipelineResult
    {
        foreach ($stageOrder as $stageName) {
            $stage = $this->resolve($stageName);

            if ($stage === null) {
                throw new ImportStageNotFoundException($stageName, $state->context);
            }

            if (! $stage->supports($state->context, $state->definition)) {
                $state->skippedStages[] = $stageName;

                continue;
            }

            $this->logger?->stageStarted($stageName, $state->context);

            $state->payload = $stage->handle($state->payload, $state->context, $state->definition, $state);

            $state->executedStages[] = $stageName;

            $this->logger?->stageCompleted($stageName, $state->context, $state->payload);
        }

        $this->logActivity($state);

        return $this->buildResult($state);
    }

    private function resolve(string $name): ?ImportPipelineStage
    {
        if (is_array($this->stages) && array_key_exists($name, $this->stages)) {
            return $this->stages[$name];
        }

        $class = 'App\\Services\\Import\\Pipeline\\'.Str::studly($name).'Stage';

        if ($this->container !== null) {
            try {
                return $this->container->make($class);
            } catch (\Throwable) {
                return null;
            }
        }

        return class_exists($class) ? new $class : null;
    }

    private function logActivity(ImportPipelineState $state): void
    {
        $logger = $state->definition->activityLogger();

        if ($state->commit !== null) {
            $logger->logCommit($state->context, $state->commit);
        } elseif ($state->preview !== null) {
            $logger->logPreview($state->context, $state->preview);
        }
    }

    private function buildResult(ImportPipelineState $state): ImportPipelineResult
    {
        $status = match (true) {
            $state->commit !== null => 'completed',
            $state->preview !== null => 'previewed',
            default => 'processed',
        };

        return new ImportPipelineResult(
            status: $status,
            preview: $state->preview,
            commit: $state->commit,
            summary: $state->summary ?? $state->validationSummary,
            stages: $state->executedStages,
            rows: $state->rows ?? [],
            statistics: $state->statistics,
        );
    }
}
