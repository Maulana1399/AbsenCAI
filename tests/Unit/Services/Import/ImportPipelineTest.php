<?php

use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Exceptions\ImportStageNotFoundException;
use App\Services\Import\Pipeline\DefaultImportPipeline;
use App\Services\Import\Pipeline\ImportPipelineState;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use App\Services\Import\Support\ArrayPipelineStageRunner;
use Tests\Unit\Services\Import\Fakes\FakeImportDefinition;
use Tests\Unit\Services\Import\Fakes\RecordingStage;

uses(Tests\TestCase::class);

function importRecordingStages(): array
{
    $map = [];

    foreach (DefaultImportPipeline::DEFAULT_STAGES as $name) {
        $map[$name] = new RecordingStage($name);
    }

    return $map;
}

beforeEach(function () {
    RecordingStage::reset();
    $this->definition = new FakeImportDefinition(keyValue: 'fake');
    $this->context = new ImportContext(type: 'fake', mode: 'execute');
});

test('canonical default stage order matches the documented pipeline', function () {
    expect(DefaultImportPipeline::DEFAULT_STAGES)->toBe([
        'parse',
        'normalize',
        'validate',
        'duplicate',
        'preview',
        'commit',
        'summary',
        'cleanup',
    ]);
});

test('pipeline exposes its configured stage order', function () {
    $pipeline = new DefaultImportPipeline(
        new ArrayPipelineStageRunner(stages: importRecordingStages()),
        ['parse', 'cleanup'],
    );

    expect($pipeline->stageOrder())->toBe(['parse', 'cleanup']);
});

test('runner executes every stage in order', function () {
    $pipeline = new DefaultImportPipeline(new ArrayPipelineStageRunner(stages: importRecordingStages()));

    $result = $pipeline->run($this->definition, $this->context, 'SOURCE');

    expect(RecordingStage::$executed)->toBe(DefaultImportPipeline::DEFAULT_STAGES)
        ->and($result->stages)->toBe(DefaultImportPipeline::DEFAULT_STAGES);
});

test('runner respects a custom stage order', function () {
    $pipeline = new DefaultImportPipeline(
        new ArrayPipelineStageRunner(stages: importRecordingStages()),
        ['parse', 'cleanup'],
    );

    $pipeline->run($this->definition, $this->context, 'SOURCE');

    expect(RecordingStage::$executed)->toBe(['parse', 'cleanup']);
});

test('runner skips stages that do not support the context', function () {
    $stages = importRecordingStages();
    $stages['preview'] = new RecordingStage('preview', supported: false);

    $pipeline = new DefaultImportPipeline(new ArrayPipelineStageRunner(stages: $stages));

    $pipeline->run($this->definition, $this->context, 'SOURCE');

    expect(RecordingStage::$executed)->not->toContain('preview');
});

test('payload flows from one stage to the next', function () {
    $stages = [
        'parse' => new RecordingStage('parse', transform: fn ($p) => ['raw']),
        'normalize' => new RecordingStage('normalize', transform: fn ($p) => strtoupper($p[0])),
        'cleanup' => new RecordingStage('cleanup'),
    ];

    $pipeline = new DefaultImportPipeline(new ArrayPipelineStageRunner(stages: $stages), ['parse', 'normalize', 'cleanup']);

    $pipeline->run($this->definition, $this->context, 'SOURCE');

    expect(RecordingStage::$payloads['parse'])->toBe('SOURCE')
        ->and(RecordingStage::$payloads['normalize'])->toBe(['raw'])
        ->and(RecordingStage::$payloads['cleanup'])->toBe('RAW');
});

test('runner throws typed exception when a stage cannot be resolved', function () {
    $pipeline = new DefaultImportPipeline(
        new ArrayPipelineStageRunner(stages: ['parse' => new RecordingStage('parse')]),
        ['parse', 'bogus-stage'],
    );

    $pipeline->run($this->definition, $this->context, 'SOURCE');
})->throws(ImportStageNotFoundException::class);

test('real commit stage commits in execute mode', function () {
    $tracker = (object) ['calls' => 0];

    $committer = new class($tracker) implements ImportCommitter
    {
        public function __construct(public object $tracker) {}

        public function commit(ImportContext $context): ImportCommit
        {
            $this->tracker->calls++;

            return new ImportCommit($context, new ImportSummary(createdRows: 1));
        }
    };

    $definition = new FakeImportDefinition(keyValue: 'fake', committer: $committer);

    $pipeline = new DefaultImportPipeline(new ArrayPipelineStageRunner);

    $result = $pipeline->run($definition, $this->context, 'SOURCE');

    expect($tracker->calls)->toBe(1)
        ->and($result->status)->toBe('completed')
        ->and($result->commit)->not->toBeNull()
        ->and($result->commit?->summary->createdRows)->toBe(1)
        ->and($result->stages)->toBe(DefaultImportPipeline::DEFAULT_STAGES);
});

test('real commit stage is skipped in preview mode', function () {
    $committer = new class implements ImportCommitter
    {
        public function commit(ImportContext $context): ImportCommit
        {
            throw new LogicException('Commit harus di-skip pada mode preview.');
        }
    };

    $definition = new FakeImportDefinition(keyValue: 'fake', committer: $committer);
    $context = new ImportContext(type: 'fake', mode: 'preview');

    $pipeline = new DefaultImportPipeline(new ArrayPipelineStageRunner);

    $result = $pipeline->run($definition, $context, 'SOURCE');

    expect($result->status)->toBe('previewed')
        ->and($result->commit)->toBeNull()
        ->and($result->stages)->not->toContain('commit');
});

test('summary stage aggregates validation and commit results', function () {
    $state = new ImportPipelineState(
        context: new ImportContext(type: 'fake', mode: 'execute'),
        definition: new FakeImportDefinition(keyValue: 'fake'),
        payload: null,
    );

    $state->rows = [
        ['nama' => 'A'],
        ['nama' => 'B'],
    ];
    $state->validationSummary = new ImportSummary(
        totalRows: 2,
        validRows: 1,
        invalidRows: 1,
        errors: [new \App\Services\Import\DTO\ImportError(2, 'nama', 'Wajib diisi.')],
    );
    $state->commit = new ImportCommit(
        context: $state->context,
        summary: new ImportSummary(createdRows: 1),
    );

    $stage = new \App\Services\Import\Pipeline\SummaryStage;
    $stage->handle($state->payload, $state->context, $state->definition, $state);

    expect($state->summary)->not->toBeNull()
        ->and($state->summary->totalRows)->toBe(2)
        ->and($state->summary->validRows)->toBe(1)
        ->and($state->summary->invalidRows)->toBe(1)
        ->and($state->summary->createdRows)->toBe(1)
        ->and($state->summary->errors)->toHaveCount(1);
});
