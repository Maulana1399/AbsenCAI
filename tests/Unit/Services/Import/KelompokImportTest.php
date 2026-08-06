<?php

use App\Models\desa;
use App\Models\kelompok;
use App\Services\Import\Adapters\Kelompok\KelompokImportDefinition;
use App\Services\Import\Contracts\ImportTemplate;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function kelompok_context(int $desaId, string $mode = 'execute'): ImportContext
{
    return new ImportContext(
        type: 'kelompok',
        mode: $mode,
        definitionKey: 'kelompok',
        options: ['file' => null, 'parameters' => ['desa_id' => $desaId]],
    );
}

function kelompok_normalized_rows(array $names, int $desaId): array
{
    $rows = [];
    $index = 0;

    foreach ($names as $name) {
        $index++;
        $name = trim($name);
        $raw = new RawImportRow($index + 1, $index, ['kelompok' => $name], ['kelompok' => $name]);
        $rows[] = new NormalizedImportRow(
            $index + 1,
            ['kelompok' => $name, 'desa_id' => $desaId],
            $raw,
            duplicateKey: $name !== '' && $desaId > 0 ? $desaId.'|'.mb_strtolower($name) : null,
        );
    }

    return $rows;
}

test('kelompok definition exposes metadata', function () {
    $definition = app(KelompokImportDefinition::class);

    expect($definition->displayName())->toBe('Import Kelompok')
        ->and($definition->description())->toContain('kelompok')
        ->and($definition->icon())->not->toBe('')
        ->and($definition->key())->toBe('kelompok')
        ->and($definition->parameters())->toHaveKey('desa_id')
        ->and($definition->parameters()['desa_id']['required'])->toBeTrue()
        ->and($definition->parameters()['desa_id']['type'])->toBe('select')
        ->and($definition->columns())->toHaveKey('kelompok')
        ->and($definition->rules())->toHaveKey('kelompok');
});

test('kelompok definition parameter options lists all desas', function () {
    desa::create(['desa_asal' => 'Desa B']);
    desa::create(['desa_asal' => 'Desa A']);

    $definition = app(KelompokImportDefinition::class);

    $options = $definition->parameterOptions('desa_id', kelompok_context(0));

    expect($options)->toHaveCount(2)
        ->and(array_values($options))->toBe(['Desa A', 'Desa B']);
});

test('kelompok definition template includes desa references', function () {
    desa::create(['desa_asal' => 'Desa Referensi']);

    $definition = app(KelompokImportDefinition::class);
    $template = $definition->template();

    expect($template)->toBeInstanceOf(ImportTemplate::class)
        ->and($template->fileName())->toBe('template_import_kelompok.xlsx');

    $sheets = $template->toExport()->sheets();
    expect($sheets)->toHaveCount(3)
        ->and($sheets[0]->title())->toBe('DATA')
        ->and($sheets[0]->headings())->toBe([['kelompok']])
        ->and($sheets[1]->title())->toBe('PETUNJUK')
        ->and($sheets[2]->title())->toBe('REFERENSI')
        ->and($sheets[2]->array())->toBe([['Desa Referensi']]);
});

test('kelompok normalizer attaches desa and derives duplicate key', function () {
    $desa = desa::create(['desa_asal' => 'Desa A']);
    $definition = app(KelompokImportDefinition::class);

    $normalized = $definition->normalize([
        new RawImportRow(2, 1, ['kelompok' => '  Kelompok   Satu '], ['kelompok' => '  Kelompok   Satu ']),
    ], kelompok_context($desa->id));

    expect($normalized[0]->data['kelompok'])->toBe('Kelompok Satu')
        ->and($normalized[0]->data['desa_id'])->toBe($desa->id)
        ->and($normalized[0]->duplicateKey)->toBe($desa->id.'|kelompok satu');
});

test('kelompok definition validateRow flags empty kelompok', function () {
    $definition = app(KelompokImportDefinition::class);
    $context = kelompok_context(1);

    expect($definition->validateRow(['kelompok' => ''], $context))->toHaveCount(1)
        ->and($definition->validateRow(['kelompok' => 'Kelompok A'], $context))->toBe([]);
});

test('kelompok validator flags empty rows and invalid desa', function () {
    desa::create(['desa_asal' => 'Desa A']);
    $definition = app(KelompokImportDefinition::class);

    $rows = kelompok_normalized_rows(['Kelompok A', ''], 1);

    $summary = $definition->validator()->validate($rows, kelompok_context(1));

    expect($summary->totalRows)->toBe(2)
        ->and($summary->validRows)->toBe(1)
        ->and($summary->invalidRows)->toBe(1)
        ->and($summary->errors)->toHaveCount(1)
        ->and($summary->errors[0])->toBeInstanceOf(ImportError::class)
        ->and($summary->errors[0]->field)->toBe('kelompok');
});

test('kelompok validator rejects invalid desa for the whole file', function () {
    $definition = app(KelompokImportDefinition::class);

    $rows = kelompok_normalized_rows(['Kelompok A'], 999);

    $summary = $definition->validator()->validate($rows, kelompok_context(999));

    expect($summary->invalidRows)->toBe(1)
        ->and($summary->errors[0]->field)->toBe('desa');
});

test('kelompok duplicate detector counts database and intra-file duplicates', function () {
    $desa = desa::create(['desa_asal' => 'Desa A']);
    kelompok::create(['kelompok_asal' => 'Kelompok Ada', 'desa_id' => $desa->id]);

    $definition = app(KelompokImportDefinition::class);
    $rows = kelompok_normalized_rows(['Kelompok Ada', 'Kelompok Baru', 'Kelompok Baru'], $desa->id);

    $summary = $definition->duplicate($rows, kelompok_context($desa->id));

    expect($summary->duplicateRows)->toBe(2)
        ->and($summary->totalRows)->toBe(3);
});

test('kelompok duplicate detection is scoped per desa', function () {
    $desaA = desa::create(['desa_asal' => 'Desa A']);
    $desaB = desa::create(['desa_asal' => 'Desa B']);
    kelompok::create(['kelompok_asal' => 'Kelompok Sama', 'desa_id' => $desaA->id]);

    $definition = app(KelompokImportDefinition::class);

    // Same name in a different desa is NOT a duplicate.
    $summary = $definition->duplicate(kelompok_normalized_rows(['Kelompok Sama'], $desaB->id), kelompok_context($desaB->id));

    expect($summary->duplicateRows)->toBe(0);
});

test('kelompok committer creates rows under the selected desa', function () {
    $desa = desa::create(['desa_asal' => 'Desa A']);
    $definition = app(KelompokImportDefinition::class);

    $commit = $definition->commit(
        kelompok_normalized_rows(['Kelompok A', 'Kelompok B'], $desa->id),
        kelompok_context($desa->id),
    );

    expect($commit->summary->createdRows)->toBe(2)
        ->and($commit->createdIds)->toHaveCount(2);

    $this->assertDatabaseHas('kelompoks', ['kelompok_asal' => 'Kelompok A', 'desa_id' => $desa->id]);
    $this->assertDatabaseHas('kelompoks', ['kelompok_asal' => 'Kelompok B', 'desa_id' => $desa->id]);
});

test('kelompok committer skips existing duplicates', function () {
    $desa = desa::create(['desa_asal' => 'Desa A']);
    kelompok::create(['kelompok_asal' => 'Kelompok Ada', 'desa_id' => $desa->id]);

    $definition = app(KelompokImportDefinition::class);
    $commit = $definition->commit(
        kelompok_normalized_rows(['Kelompok Ada', 'Kelompok Baru'], $desa->id),
        kelompok_context($desa->id),
    );

    expect($commit->summary->createdRows)->toBe(1)
        ->and($commit->summary->skippedRows)->toBe(1)
        ->and($commit->skippedIds)->toBe([2]);

    $this->assertSame(1, kelompok::where('kelompok_asal', 'Kelompok Ada')->count());
});
