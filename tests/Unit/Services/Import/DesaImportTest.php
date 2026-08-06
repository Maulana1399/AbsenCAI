<?php

use App\Models\desa;
use App\Services\Import\Adapters\Desa\DesaImportDefinition;
use App\Services\Import\Contracts\ImportTemplate;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function desa_context(string $mode = 'execute'): ImportContext
{
    return new ImportContext(type: 'desa', mode: $mode, definitionKey: 'desa');
}

function desa_normalized_rows(array $names): array
{
    $rows = [];
    $index = 0;

    foreach ($names as $name) {
        $index++;
        $raw = new RawImportRow($index + 1, $index, ['desa' => $name], ['desa' => $name]);
        $rows[] = new NormalizedImportRow($index + 1, ['desa' => trim($name)], $raw, duplicateKey: trim($name) !== '' ? mb_strtolower(trim($name)) : null);
    }

    return $rows;
}

test('desa definition exposes columns and rules', function () {
    $definition = app(DesaImportDefinition::class);

    expect($definition->columns())->toHaveKey('desa')
        ->and($definition->columns()['desa']['required'])->toBeTrue()
        ->and($definition->rules())->toHaveKey('desa')
        ->and($definition->label())->toBe('Import Desa')
        ->and($definition->key())->toBe('desa');
});

test('desa definition template returns a 3-sheet import template', function () {
    $definition = app(DesaImportDefinition::class);

    $template = $definition->template();

    expect($template)->toBeInstanceOf(ImportTemplate::class)
        ->and($template->fileName())->toBe('template_import_desa.xlsx');

    $sheets = $template->toExport()->sheets();
    expect($sheets)->toHaveCount(3)
        ->and($sheets[0]->title())->toBe('DATA')
        ->and($sheets[1]->title())->toBe('PETUNJUK')
        ->and($sheets[2]->title())->toBe('REFERENSI')
        ->and($sheets[0]->headings())->toBe([['desa']]);
});

test('desa definition normalize trims and derives duplicate key', function () {
    $definition = app(DesaImportDefinition::class);

    $rows = desa_normalized_rows(['  Desa   Satu  ']);

    $normalized = $definition->normalize([
        new RawImportRow(2, 1, ['desa' => '  Desa   Satu  '], ['desa' => '  Desa   Satu  ']),
    ], desa_context());

    expect($normalized[0]->data['desa'])->toBe('Desa Satu')
        ->and($normalized[0]->duplicateKey)->toBe('desa satu');
});

test('desa definition validateRow flags empty desa', function () {
    $definition = app(DesaImportDefinition::class);
    $context = desa_context();

    expect($definition->validateRow(['desa' => ''], $context))->toHaveCount(1)
        ->and($definition->validateRow(['desa' => '  '], $context))->toHaveCount(1)
        ->and($definition->validateRow(['desa' => 'Desa Satu'], $context))->toBe([]);
});

test('desa validator flags empty rows as errors', function () {
    $definition = app(DesaImportDefinition::class);

    $rows = [
        ...desa_normalized_rows(['Desa Satu']),
        new NormalizedImportRow(
            3,
            ['desa' => ''],
            new RawImportRow(3, 2, ['desa' => ''], ['desa' => '']),
        ),
    ];

    $summary = $definition->validator()->validate($rows, desa_context());

    expect($summary->totalRows)->toBe(2)
        ->and($summary->validRows)->toBe(1)
        ->and($summary->invalidRows)->toBe(1)
        ->and($summary->errors[0])->toBeInstanceOf(ImportError::class)
        ->and($summary->errors[0]->rowNumber)->toBe(3);
});

test('desa duplicate detector counts database and intra-file duplicates', function () {
    desa::create(['desa_asal' => 'Desa Ada']);

    $definition = app(DesaImportDefinition::class);

    $rows = desa_normalized_rows(['Desa Ada', 'Desa Baru', 'Desa Baru', 'Desa Baru']);

    $summary = $definition->duplicate($rows, desa_context());

    // Desa Ada = DB duplicate; Desa Baru appears 3x → 2 intra-file duplicates.
    expect($summary->duplicateRows)->toBe(3)
        ->and($summary->totalRows)->toBe(4)
        ->and($summary->validRows)->toBe(1);
});

test('desa committer creates new rows and skips existing duplicates', function () {
    desa::create(['desa_asal' => 'Desa Ada']);

    $definition = app(DesaImportDefinition::class);
    $context = desa_context();

    $rows = desa_normalized_rows(['Desa Ada', 'Desa Baru', '  Desa Baru  ']);

    $commit = $definition->commit($rows, $context);

    expect($commit->createdIds)->toHaveCount(1)
        ->and($commit->skippedIds)->toHaveCount(2)
        ->and($commit->summary->createdRows)->toBe(1)
        ->and($commit->summary->skippedRows)->toBe(2);

    $this->assertDatabaseHas('desas', ['desa_asal' => 'Desa Baru']);
    $this->assertSame(1, desa::where('desa_asal', 'Desa Ada')->count());
});

test('desa committer records failed rows for empty names', function () {
    $definition = app(DesaImportDefinition::class);

    $rows = [
        ...desa_normalized_rows(['Desa Baru']),
        new NormalizedImportRow(
            3,
            ['desa' => ''],
            new RawImportRow(3, 2, ['desa' => ''], ['desa' => '']),
        ),
    ];

    $commit = $definition->commit($rows, desa_context());

    expect($commit->failedRows)->toHaveCount(1)
        ->and($commit->failedRows[0]['row'])->toBe(3)
        ->and($commit->summary->invalidRows)->toBe(1);
});
