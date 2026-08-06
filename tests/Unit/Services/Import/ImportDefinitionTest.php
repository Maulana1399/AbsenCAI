<?php

use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportSummary;
use Tests\Unit\Services\Import\Fakes\FakeImportDefinition;

uses(Tests\TestCase::class);

test('definition exposes its key and label', function () {
    $definition = new FakeImportDefinition(keyValue: 'desa', labelValue: 'Import Desa');

    expect($definition->key())->toBe('desa')
        ->and($definition->label())->toBe('Import Desa');
});

test('definition exposes its collaborators', function () {
    $parser = new \App\Services\Import\NullObjects\NullImportParser;
    $validator = new \App\Services\Import\NullObjects\NullImportValidator;
    $normalizer = new \App\Services\Import\NullObjects\NullImportNormalizer;
    $duplicate = new \App\Services\Import\NullObjects\NullImportDuplicateDetector;
    $committer = new class implements ImportCommitter
    {
        public function commit(ImportContext $context): ImportCommit
        {
            return new ImportCommit($context, new ImportSummary);
        }
    };
    $logger = new \App\Services\Import\NullObjects\NullImportActivityLogger;

    $definition = new FakeImportDefinition(
        parser: $parser,
        validator: $validator,
        normalizer: $normalizer,
        duplicateDetector: $duplicate,
        committer: $committer,
        activityLogger: $logger,
    );

    expect($definition->parser())->toBe($parser)
        ->and($definition->validator())->toBe($validator)
        ->and($definition->normalizer())->toBe($normalizer)
        ->and($definition->duplicateDetector())->toBe($duplicate)
        ->and($definition->committer())->toBe($committer)
        ->and($definition->activityLogger())->toBe($logger);
});

test('definition exposes version contract', function () {
    $definition = new FakeImportDefinition(
        current: '2.1.0',
        minimum: '1.0.0',
        supported: '2.1.0',
    );

    expect($definition->currentVersion())->toBe('2.1.0')
        ->and($definition->minimumVersion())->toBe('1.0.0')
        ->and($definition->supportedVersion())->toBe('2.1.0');
});

test('definition declares preview and commit capabilities', function () {
    $context = new ImportContext(type: 'fake');

    $previewOnly = new FakeImportDefinition(preview: true, commit: false);
    $commitOnly = new FakeImportDefinition(preview: false, commit: true);

    expect($previewOnly->supportsPreview($context))->toBeTrue()
        ->and($previewOnly->supportsCommit($context))->toBeFalse()
        ->and($commitOnly->supportsPreview($context))->toBeFalse()
        ->and($commitOnly->supportsCommit($context))->toBeTrue();
});
