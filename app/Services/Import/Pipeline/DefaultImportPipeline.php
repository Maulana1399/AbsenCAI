<?php

namespace App\Services\Import\Pipeline;

use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportPipelineResult;
use App\Services\Import\Results\ImportPreview;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use App\Services\Import\Support\PipelineStageRunner;

final class DefaultImportPipeline implements ImportPipeline
{
    public function __construct(
        private readonly PipelineStageRunner $runner,
    ) {
    }

    public function run(ImportDefinition $definition, ImportContext $context, mixed $source): ImportPipelineResult
    {
        $stages = [];

        $payload = $this->runner->run('preflight', $source, $context);
        $stages[] = 'preflight';

        $payload = $this->runner->run('parser', $payload, $context);
        $stages[] = 'parser';

        $payload = $this->runner->run('normalize', $payload, $context);
        $stages[] = 'normalize';

        $summary = $this->runner->run('validate', $payload, $context);
        $stages[] = 'validate';

        if (! $summary instanceof ImportSummary) {
            $summary = new ImportSummary();
        }

        $payload = $this->runner->run('duplicate', $payload, $context);
        $stages[] = 'duplicate';

        $preview = $this->runner->run('preview', $payload, $context);
        $stages[] = 'preview';

        if (! $preview instanceof ImportPreview) {
            $preview = new ImportPreview($context, [], $summary, false);
        }

        $commit = $definition->committer()->commit($context);
        $stages[] = 'commit';

        $payload = $this->runner->run('activity-log', $commit, $context);
        $stages[] = 'activity-log';

        return new ImportPipelineResult(
            status: 'completed',
            preview: $preview,
            commit: $commit,
            summary: $summary,
            stages: $stages,
        );
    }
}
