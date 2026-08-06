<?php

use App\Models\desa;
use App\Models\kelompok;
use App\Models\Person;
use App\Services\Import\Adapters\Person\PersonImportDefinition;
use App\Services\Import\Contracts\ImportTemplate;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function person_context(string $mode = 'execute'): ImportContext
{
    return new ImportContext(type: 'person', mode: $mode, definitionKey: 'person');
}

function person_normalized_row(int $rowNumber, array $data, ?string $duplicateKey = null): NormalizedImportRow
{
    return new NormalizedImportRow(
        $rowNumber,
        $data,
        new RawImportRow($rowNumber, $rowNumber - 2, $data, $data),
        duplicateKey: $duplicateKey,
    );
}

beforeEach(function () {
    $this->definition = app(PersonImportDefinition::class);
    $this->desa = desa::create(['desa_asal' => 'Desa Audit']);
    $this->kelompok = kelompok::create(['kelompok_asal' => 'Kelompok Audit', 'desa_id' => $this->desa->id]);
});

test('person definition exposes metadata with no parameter', function () {
    expect($this->definition->displayName())->toBe('Import Person')
        ->and($this->definition->description())->toContain('Person')
        ->and($this->definition->icon())->not->toBe('')
        ->and($this->definition->key())->toBe('person')
        ->and($this->definition->parameters())->toBe([])
        ->and($this->definition->columns())->toHaveKeys(['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'])
        ->and($this->definition->rules())->toHaveKeys(['nama', 'jenis_kelamin', 'tanggal_lahir']);
});

test('person definition template exposes gender and desa references', function () {
    $template = $this->definition->template();

    expect($template)->toBeInstanceOf(ImportTemplate::class)
        ->and($template->fileName())->toBe('template_import_person.xlsx');

    $sheets = $template->toExport()->sheets();
    $reference = $sheets[2]->array();

    expect($sheets)->toHaveCount(3)
        ->and($sheets[0]->title())->toBe('DATA')
        ->and($sheets[0]->headings())->toBe([['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok']])
        ->and($sheets[1]->title())->toBe('PETUNJUK')
        ->and($sheets[2]->title())->toBe('REFERENSI')
        ->and($reference)->toContain(['Laki - Laki (L)'])
        ->and($reference)->toContain(['Perempuan (P)'])
        ->and($reference)->toContain(['Desa Audit']);
});

test('person normalizer trims, collapses spaces and normalizes gender', function () {
    $normalized = $this->definition->normalize([
        new RawImportRow(2, 1, [
            'nama' => '  Ahmad   Wijaya  ',
            'jenis_kelamin' => 'laki laki',
            'tanggal_lahir' => '2000-01-15',
            'desa' => 'Desa Audit',
            'kelompok' => 'Kelompok Audit',
        ], ['nama' => '  Ahmad   Wijaya  ']),
        new RawImportRow(3, 2, [
            'nama' => 'Siti Rahmawati',
            'jenis_kelamin' => 'Perempuan',
            'tanggal_lahir' => '1998-06-20',
            'desa' => 'Desa Audit',
        ], ['nama' => 'Siti Rahmawati']),
    ], person_context());

    expect($normalized[0]->data['nama'])->toBe('Ahmad Wijaya')
        ->and($normalized[0]->data['jenis_kelamin'])->toBe('L')
        ->and($normalized[0]->data['tanggal_lahir'])->toBe('2000-01-15')
        ->and($normalized[0]->data['desa_id'])->toBe($this->desa->id)
        ->and($normalized[0]->data['kelompok_id'])->toBe($this->kelompok->id)
        ->and($normalized[0]->duplicateKey)->toBe('ahmad wijaya|'.$this->desa->id.'|2000-01-15')
        ->and($normalized[1]->data['jenis_kelamin'])->toBe('P');
});

test('person normalizer handles unicode spaces', function () {
    $normalized = $this->definition->normalize([
        new RawImportRow(2, 1, ['nama' => "Ahmad\u{00A0}\u{2009}Wijaya", 'jenis_kelamin' => 'P'], ['nama' => 'x']),
    ], person_context());

    expect($normalized[0]->data['nama'])->toBe('Ahmad Wijaya');
});

test('person normalizer does not resolve unknown desa or kelompok', function () {
    $normalized = $this->definition->normalize([
        new RawImportRow(2, 1, [
            'nama' => 'Budi',
            'jenis_kelamin' => 'L',
            'desa' => 'Desa Tidak Ada',
            'kelompok' => 'Kelompok Tidak Ada',
        ], ['nama' => 'Budi']),
    ], person_context());

    expect($normalized[0]->data['desa_id'])->toBeNull()
        ->and($normalized[0]->data['kelompok_id'])->toBeNull();
});

test('person validator flags invalid rows', function () {
    $rows = [
        person_normalized_row(2, ['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id], 'ahmad wijaya|1|2000-01-15'),
        person_normalized_row(3, ['nama' => '', 'jenis_kelamin' => 'X', 'tanggal_lahir' => 'not-a-date', 'desa' => 'Desa Tidak Ada', 'kelompok' => 'Kelompok Salah']),
    ];

    $summary = $this->definition->validator()->validate($rows, person_context());

    expect($summary->totalRows)->toBe(2)
        ->and($summary->validRows)->toBe(1)
        ->and($summary->invalidRows)->toBe(1)
        ->and(collect($summary->errors)->pluck('field'))->toContain('nama')
        ->and(collect($summary->errors)->pluck('field'))->toContain('jenis_kelamin')
        ->and(collect($summary->errors)->pluck('field'))->toContain('tanggal_lahir')
        ->and(collect($summary->errors)->pluck('field'))->toContain('desa')
        ->and(collect($summary->errors)->pluck('field'))->toContain('kelompok');
});

test('person duplicate detector reuses canonical duplicate service', function () {
    Person::create(['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id]);

    $rows = [
        person_normalized_row(2, ['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id], 'ahmad wijaya|1|2000-01-15'),
        person_normalized_row(3, ['nama' => 'Budi Santoso', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '1999-03-03', 'desa_id' => $this->desa->id], 'budi santoso|1|1999-03-03'),
    ];

    $summary = $this->definition->duplicate($rows, person_context());

    expect($summary->duplicateRows)->toBe(1)
        ->and($summary->totalRows)->toBe(2);
});

test('person duplicate detector flags intra-file duplicates', function () {
    $rows = [
        person_normalized_row(2, ['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id], 'ahmad wijaya|1|2000-01-15'),
        person_normalized_row(3, ['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id], 'ahmad wijaya|1|2000-01-15'),
    ];

    $summary = $this->definition->duplicate($rows, person_context());

    expect($summary->duplicateRows)->toBe(1);
});

test('person committer creates new persons and skips duplicates', function () {
    Person::create(['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id]);

    $rows = [
        person_normalized_row(2, ['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id], 'ahmad wijaya|1|2000-01-15'),
        person_normalized_row(3, ['nama' => 'Budi Santoso', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '1999-03-03', 'desa_id' => $this->desa->id], 'budi santoso|1|1999-03-03'),
    ];

    $commit = $this->definition->commit($rows, person_context());

    expect($commit->summary->createdRows)->toBe(1)
        ->and($commit->summary->skippedRows)->toBe(1)
        ->and($commit->createdIds)->toHaveCount(1);

    $budi = Person::where('nama', 'Budi Santoso')->first();
    expect($budi)->not->toBeNull()
        ->and($budi->jenis_kelamin)->toBe('L')
        ->and($budi->tanggal_lahir->format('Y-m-d'))->toBe('1999-03-03')
        ->and($budi->desa_id)->toBe($this->desa->id);

    $this->assertSame(1, Person::where('nama', 'Ahmad Wijaya')->count());
});

test('person committer records failed rows for invalid identity', function () {
    $rows = [
        person_normalized_row(2, ['nama' => '', 'jenis_kelamin' => 'X']),
    ];

    $commit = $this->definition->commit($rows, person_context());

    expect($commit->failedRows)->toHaveCount(1)
        ->and($commit->summary->invalidRows)->toBe(1)
        ->and($commit->createdIds)->toBe([]);
});
