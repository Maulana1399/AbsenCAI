<?php

use App\Services\Import\Registry\ImportRegistry;
use Tests\Unit\Services\Import\Fakes\FakeImportDefinition;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->registry = new ImportRegistry;
});

test('registry resolves a registered definition', function () {
    $definition = new FakeImportDefinition(keyValue: 'desa');

    $this->registry->register($definition);

    expect($this->registry->resolve('desa'))->toBe($definition);
});

test('registry resolve returns null for unknown key', function () {
    expect($this->registry->resolve('missing'))->toBeNull();
});

test('registry has returns true only for registered keys', function () {
    $this->registry->register(new FakeImportDefinition(keyValue: 'desa'));

    expect($this->registry->has('desa'))->toBeTrue()
        ->and($this->registry->has('kelompok'))->toBeFalse();
});

test('registry all returns definitions keyed by definition key', function () {
    $this->registry->register(new FakeImportDefinition(keyValue: 'desa'));
    $this->registry->register(new FakeImportDefinition(keyValue: 'kelompok'));

    expect(array_keys($this->registry->all()))->toBe(['desa', 'kelompok'])
        ->and($this->registry->all()['desa'])->toBeInstanceOf(FakeImportDefinition::class);
});

test('registry definitions returns the same map as all', function () {
    $this->registry->register(new FakeImportDefinition(keyValue: 'desa'));

    expect($this->registry->definitions())->toBe($this->registry->all());
});

test('registry re-registering the same key overrides the previous definition', function () {
    $first = new FakeImportDefinition(keyValue: 'desa');
    $second = new FakeImportDefinition(keyValue: 'desa');

    $this->registry->register($first);
    $this->registry->register($second);

    expect($this->registry->resolve('desa'))->toBe($second);
});
