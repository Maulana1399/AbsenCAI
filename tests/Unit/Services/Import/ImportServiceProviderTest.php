<?php

use App\Services\Import\Pipeline\DefaultImportPipeline;
use App\Services\Import\Pipeline\ImportCoordinator;
use App\Services\Import\Pipeline\ImportPipeline;
use App\Services\Import\Registry\ImportRegistry;
use App\Services\Import\Support\PipelineStageRunner;

uses(Tests\TestCase::class);

test('import registry is resolved as a container singleton', function () {
    $first = app(ImportRegistry::class);
    $second = app(ImportRegistry::class);

    expect($first)->toBe($second);
});

test('provider pre-registers complete definitions into the singleton registry', function () {
    $registry = app(ImportRegistry::class);

    expect($registry->has('desa'))->toBeTrue()
        ->and($registry->has('kelompok'))->toBeTrue()
        ->and($registry->has('regu'))->toBeTrue()
        ->and($registry->has('peserta'))->toBeTrue()
        ->and($registry->has('pengajian'))->toBeFalse();
});

test('pipeline and coordinator are resolvable from the container', function () {
    $pipeline = app(ImportPipeline::class);
    $coordinator = app(ImportCoordinator::class);

    expect($pipeline)->toBeInstanceOf(DefaultImportPipeline::class)
        ->and($pipeline->stageOrder())->toBe(DefaultImportPipeline::DEFAULT_STAGES)
        ->and($coordinator)->toBeInstanceOf(ImportCoordinator::class);
});

test('pipeline stage runner is bound to the concrete runner', function () {
    expect(app(PipelineStageRunner::class))
        ->toBeInstanceOf(\App\Services\Import\Support\ArrayPipelineStageRunner::class);
});
