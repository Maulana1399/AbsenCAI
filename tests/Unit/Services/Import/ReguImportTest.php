<?php

use App\Models\regu;
use App\Services\Import\Adapters\Regu\ReguImportDefinition;
use App\Services\Import\Contracts\ImportTemplate;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function regu_context(string $mode = 'execute'): ImportContext
{
    return new ImportContext(type: 'regu', mode: $mode, definitionKey: 'regu');
}

function regu_normalized_rows(array $pairs): array
{
    $rows = [];
    $index = 0;

    foreach ($pairs as [$regu, $gender]) {
        $index++;
        $regu = trim($regu);
        $raw = new RawImportRow($index + 1, $index, ['regu' => $regu, 'jenis_kelamin' => $gender], ['regu' => $regu, 'jenis_kelamin' => $gender]);
        $rows[] = new NormalizedImportRow(
            $index + 1,
            ['regu' => $regu, 'jenis_kelamin' => $gender],
            $raw,
            duplicateKey: $regu !== '' ? mb_strtolower($regu) : null,
        );
    }

    return $rows;
}

test('regu definition exposes metadata with no parameter (global entity)', function () {
    $definition = app(ReguImportDefinition::class);

    expect($definition->displayName())->toBe('Import Regu')
        ->and($definition->description())->toContain('regu')
        ->and($definition->icon())->not->toBe('')
        ->and($definition->key())->toBe('regu')
        ->and($definition->parameters())->toBe([])
        ->and($definition->columns())->toHaveKeys(['regu', 'jenis_kelamin'])
        ->and($definition->rules())->toHaveKeys(['regu', 'jenis_kelamin'])
        ->and($definition->rules()['jenis_kelamin'])->toContain('in:Laki - Laki,Perempuan');
});

test('regu definition template exposes gender references', function () {
    $definition = app(ReguImportDefinition::class);
    $template = $definition->template();

    expect($template)->toBeInstanceOf(ImportTemplate::class)
        ->and($template->fileName())->toBe('template_import_regu.xlsx');

    $sheets = $template->toExport()->sheets();
    expect($sheets)->toHaveCount(3)
        ->and($sheets[0]->title())->toBe('DATA')
        ->and($sheets[0]->headings())->toBe([['regu', 'jenis_kelamin']])
        ->and($sheets[1]->title())->toBe('PETUNJUK')
        ->and($sheets[2]->title())->toBe('REFERENSI')
        ->and($sheets[2]->array())->toBe([['Laki - Laki'], ['Perempuan']]);
});

test('regu normalizer trims name and normalizes gender variants', function () {
    $definition = app(ReguImportDefinition::class);

    $normalized = $definition->normalize([
        new RawImportRow(2, 1, ['regu' => '  Grup  Merah ', 'jenis_kelamin' => 'laki laki'], ['regu' => '  Grup  Merah ', 'jenis_kelamin' => 'laki laki']),
        new RawImportRow(3, 2, ['regu' => 'Grup Biru', 'jenis_kelamin' => 'Laki - laki'], ['regu' => 'Grup Biru', 'jenis_kelamin' => 'Laki - laki']),
        new RawImportRow(4, 3, ['regu' => 'Grup Hijau', 'jenis_kelamin' => 'Laki – Laki'], ['regu' => 'Grup Hijau', 'jenis_kelamin' => 'Laki – Laki']),
        new RawImportRow(5, 4, ['regu' => 'Grup Putih', 'jenis_kelamin' => 'perempuan'], ['regu' => 'Grup Putih', 'jenis_kelamin' => 'perempuan']),
    ], regu_context());

    expect($normalized[0]->data['regu'])->toBe('Grup Merah')
        ->and($normalized[0]->data['jenis_kelamin'])->toBe('Laki - Laki')
        ->and($normalized[0]->duplicateKey)->toBe('grup merah')
        ->and($normalized[1]->data['jenis_kelamin'])->toBe('Laki - Laki')
        ->and($normalized[2]->data['jenis_kelamin'])->toBe('Laki - Laki')
        ->and($normalized[3]->data['jenis_kelamin'])->toBe('Perempuan');
});

test('regu definition validateRow flags empty regu and invalid gender', function () {
    $definition = app(ReguImportDefinition::class);
    $context = regu_context();

    expect($definition->validateRow(['regu' => '', 'jenis_kelamin' => 'Laki - Laki'], $context))->toHaveCount(1)
        ->and($definition->validateRow(['regu' => 'Regu A', 'jenis_kelamin' => 'Unknown'], $context))->toHaveCount(1)
        ->and($definition->validateRow(['regu' => 'Regu A', 'jenis_kelamin' => 'Perempuan'], $context))->toBe([]);
});

test('regu validator flags empty rows and invalid gender', function () {
    $definition = app(ReguImportDefinition::class);

    $rows = [
        ...regu_normalized_rows([['Regu A', 'Laki - Laki']]),
        new NormalizedImportRow(3, ['regu' => '', 'jenis_kelamin' => 'Unknown'], new RawImportRow(3, 2, ['regu' => '', 'jenis_kelamin' => 'Unknown'], ['regu' => '', 'jenis_kelamin' => 'Unknown'])),
    ];

    $summary = $definition->validator()->validate($rows, regu_context());

    expect($summary->totalRows)->toBe(2)
        ->and($summary->validRows)->toBe(1)
        ->and($summary->invalidRows)->toBe(1)
        ->and($summary->errors[0])->toBeInstanceOf(ImportError::class)
        ->and(collect($summary->errors)->pluck('field'))->toContain('jenis_kelamin')
        ->and(collect($summary->errors)->pluck('field'))->toContain('regu');
});

test('regu duplicate detector uses unique regu name business rule', function () {
    regu::create(['regu' => 'Regu Ada', 'jenis_kelamin' => 'Laki - Laki']);

    $definition = app(ReguImportDefinition::class);
    $rows = regu_normalized_rows([
        ['Regu Ada', 'Laki - Laki'],
        ['Regu Ada', 'Perempuan'],
        ['Regu Baru', 'Perempuan'],
    ]);

    // Same name in any gender counts as duplicate (unique:regus,regu).
    $summary = $definition->duplicate($rows, regu_context());

    expect($summary->duplicateRows)->toBe(2)
        ->and($summary->totalRows)->toBe(3);
});

test('regu committer creates rows and skips name duplicates', function () {
    regu::create(['regu' => 'Regu Ada', 'jenis_kelamin' => 'Laki - Laki']);

    $definition = app(ReguImportDefinition::class);
    $commit = $definition->commit(
        regu_normalized_rows([
            ['Regu Ada', 'Laki - Laki'],
            ['Regu Baru', 'Perempuan'],
            ['  Regu Baru  ', 'Perempuan'],
        ]),
        regu_context(),
    );

    expect($commit->summary->createdRows)->toBe(1)
        ->and($commit->summary->skippedRows)->toBe(2);

    $this->assertSame(1, regu::where('regu', 'Regu Ada')->count());
    $this->assertDatabaseHas('regus', ['regu' => 'Regu Baru', 'jenis_kelamin' => 'Perempuan']);
});

test('regu committer records failed rows for invalid gender', function () {
    $definition = app(ReguImportDefinition::class);

    $commit = $definition->commit(
        regu_normalized_rows([
            ['Regu A', 'Unknown'],
            ['', 'Perempuan'],
        ]),
        regu_context(),
    );

    expect($commit->failedRows)->toHaveCount(2)
        ->and($commit->summary->invalidRows)->toBe(2)
        ->and($commit->createdIds)->toBe([]);
});
