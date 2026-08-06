<?php

use App\Services\Import\DTO\ImportContext;

uses(Tests\TestCase::class);

test('context defaults to sensible values', function () {
    $context = new ImportContext(type: 'desa');

    expect($context->type)->toBe('desa')
        ->and($context->eventId)->toBeNull()
        ->and($context->userId)->toBeNull()
        ->and($context->fileName)->toBeNull()
        ->and($context->source)->toBe('unknown')
        ->and($context->mode)->toBe('preview')
        ->and($context->options)->toBe([])
        ->and($context->definitionKey)->toBeNull()
        ->and($context->transactionId)->toBeNull()
        ->and($context->event)->toBeNull()
        ->and($context->user)->toBeNull()
        ->and($context->version)->toBeNull();
});

test('context carries the full set of input fields', function () {
    $context = new ImportContext(
        type: 'pengajian',
        eventId: 7,
        userId: 42,
        fileName: 'peserta.csv',
        source: 'livewire',
        mode: 'execute',
        options: ['file' => '/tmp/peserta.csv'],
        definitionKey: 'pengajian',
        transactionId: 'tx-123',
        version: '1.0.0',
    );

    expect($context->type)->toBe('pengajian')
        ->and($context->eventId)->toBe(7)
        ->and($context->userId)->toBe(42)
        ->and($context->fileName)->toBe('peserta.csv')
        ->and($context->source)->toBe('livewire')
        ->and($context->mode)->toBe('execute')
        ->and($context->options)->toBe(['file' => '/tmp/peserta.csv'])
        ->and($context->definitionKey)->toBe('pengajian')
        ->and($context->transactionId)->toBe('tx-123')
        ->and($context->version)->toBe('1.0.0');
});

test('context is immutable (readonly final value object)', function () {
    $reflection = new ReflectionClass(ImportContext::class);

    expect($reflection->isFinal())->toBeTrue()
        ->and($reflection->isReadOnly())->toBeTrue();
});
