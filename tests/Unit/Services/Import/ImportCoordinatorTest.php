<?php

use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Exceptions\ImportDefinitionNotFoundException;
use App\Services\Import\Exceptions\ImportVersionNotSupportedException;
use App\Services\Import\Pipeline\ImportCoordinator;
use App\Services\Import\Registry\ImportRegistry;
use Tests\Unit\Services\Import\Fakes\FakeImportDefinition;
use Tests\Unit\Services\Import\Fakes\FakePipeline;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->pipeline = new FakePipeline;
    $this->registry = new ImportRegistry;
    $this->coordinator = new ImportCoordinator($this->registry, $this->pipeline);
});

test('coordinator resolves definition and delegates to pipeline', function () {
    $definition = new FakeImportDefinition(keyValue: 'fake');
    $this->registry->register($definition);

    $context = new ImportContext(type: 'fake', mode: 'execute');

    $result = $this->coordinator->execute('fake', $context, 'SOURCE');

    expect($result->status)->toBe('completed')
        ->and($this->pipeline->runs)->toHaveCount(1)
        ->and($this->pipeline->runs[0])->toMatchArray([
            'definition' => 'fake',
            'mode' => 'execute',
            'source' => 'SOURCE',
        ]);
});

test('coordinator throws typed exception when definition is not registered', function () {
    $this->coordinator->execute('missing', new ImportContext(type: 'fake'), 'SOURCE');
})->throws(ImportDefinitionNotFoundException::class);

test('coordinator accepts version within the definition range', function () {
    $this->registry->register(new FakeImportDefinition(keyValue: 'fake'));

    $context = new ImportContext(type: 'fake', version: '1.5.0');

    $result = $this->coordinator->execute('fake', $context, 'SOURCE');

    expect($result->status)->toBe('completed');
});

test('coordinator throws typed exception when version is out of range', function () {
    $this->registry->register(new FakeImportDefinition(keyValue: 'fake'));

    $context = new ImportContext(type: 'fake', version: '0.4.0');

    $this->coordinator->execute('fake', $context, 'SOURCE');
})->throws(ImportVersionNotSupportedException::class);

test('coordinator skips version check when context version is null', function () {
    $this->registry->register(new FakeImportDefinition(keyValue: 'fake'));

    $result = $this->coordinator->execute('fake', new ImportContext(type: 'fake'), 'SOURCE');

    expect($result->status)->toBe('completed')
        ->and($this->pipeline->runs)->toHaveCount(1);
});
