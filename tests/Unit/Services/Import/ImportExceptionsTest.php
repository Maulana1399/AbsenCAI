<?php

use App\Services\Import\Exceptions\ImportCommitException;
use App\Services\Import\Exceptions\ImportDefinitionNotFoundException;
use App\Services\Import\Exceptions\ImportException;
use App\Services\Import\Exceptions\ImportParseException;
use App\Services\Import\Exceptions\ImportStageNotFoundException;
use App\Services\Import\Exceptions\ImportValidationException;
use App\Services\Import\Exceptions\ImportVersionNotSupportedException;
use Tests\Unit\Services\Import\Fakes\FakeImportDefinition;

uses(Tests\TestCase::class);

test('all import exceptions extend the base ImportException', function () {
    foreach ([
        ImportDefinitionNotFoundException::class,
        ImportStageNotFoundException::class,
        ImportParseException::class,
        ImportValidationException::class,
        ImportCommitException::class,
        ImportVersionNotSupportedException::class,
    ] as $exceptionClass) {
        expect(is_subclass_of($exceptionClass, ImportException::class))->toBeTrue();
    }
});

test('base ImportException is a RuntimeException subclass (typed domain base)', function () {
    expect(is_subclass_of(ImportException::class, RuntimeException::class))->toBeTrue();
});

test('definition not found exception carries the key in the message', function () {
    $exception = new ImportDefinitionNotFoundException('desa');

    expect($exception->getMessage())->toContain('desa');
});

test('version not supported exception carries version and range', function () {
    $definition = new FakeImportDefinition(keyValue: 'fake', current: '2.0.0', minimum: '1.0.0');

    $exception = new ImportVersionNotSupportedException('0.5.0', $definition);

    expect($exception->getMessage())->toContain('0.5.0')
        ->and($exception->getMessage())->toContain('1.0.0')
        ->and($exception->getMessage())->toContain('2.0.0');
});

test('parse and validation exceptions preserve the previous exception', function () {
    $previous = new RuntimeException('original');

    $parse = new ImportParseException('parse gagal', $previous);
    $validation = new ImportValidationException('validasi gagal', $previous);

    expect($parse->getPrevious())->toBe($previous)
        ->and($validation->getPrevious())->toBe($previous);
});
