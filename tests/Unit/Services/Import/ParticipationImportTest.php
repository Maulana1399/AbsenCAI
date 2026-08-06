<?php

use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\Participation;
use App\Models\Person;
use App\Models\regu;
use App\Services\Import\Adapters\Participation\ParticipationImportDefinition;
use App\Services\Import\Contracts\ImportTemplate;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\NormalizedImportRow;
use App\Services\Import\DTO\RawImportRow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function participation_context(int $eventId, string $mode = 'execute'): ImportContext
{
    return new ImportContext(
        type: 'participation',
        mode: $mode,
        definitionKey: 'participation',
        options: ['file' => null, 'parameters' => ['event_id' => $eventId]],
    );
}

function participation_normalized_row(int $rowNumber, array $data): NormalizedImportRow
{
    return new NormalizedImportRow(
        $rowNumber,
        $data,
        new RawImportRow($rowNumber, $rowNumber - 2, $data, $data),
        duplicateKey: isset($data['nama'], $data['desa_id'], $data['tanggal_lahir'])
            ? mb_strtolower($data['nama']).'|'.$data['desa_id'].'|'.$data['tanggal_lahir']
            : null,
    );
}

beforeEach(function () {
    $this->definition = app(ParticipationImportDefinition::class);
    $this->desa = desa::create(['desa_asal' => 'Desa Part']);
    $this->kelompok = kelompok::create(['kelompok_asal' => 'Kelompok Part', 'desa_id' => $this->desa->id]);
    $this->eventA = Event::create(['name' => 'Event A', 'slug' => 'event-a-'.str()->random(5), 'status' => 'active']);
    $this->eventB = Event::create(['name' => 'Event B', 'slug' => 'event-b-'.str()->random(5), 'status' => 'active']);
});

test('participation definition exposes metadata with event parameter', function () {
    expect($this->definition->displayName())->toBe('Import Participation')
        ->and($this->definition->description())->toContain('Event')
        ->and($this->definition->icon())->not->toBe('')
        ->and($this->definition->key())->toBe('participation')
        ->and($this->definition->parameters())->toHaveKey('event_id')
        ->and($this->definition->parameters()['event_id']['required'])->toBeTrue()
        ->and($this->definition->columns())->toHaveKeys(['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok', 'jenis_peserta', 'status_registrasi', 'regu'])
        ->and($this->definition->rules())->toHaveKeys(['nama', 'jenis_kelamin', 'desa']);
});

test('participation definition parameter options lists events', function () {
    $options = $this->definition->parameterOptions('event_id', participation_context($this->eventA->id));

    expect($options)->toHaveCount(2)
        ->and(array_values($options))->toBe(['Event A', 'Event B']);
});

test('participation definition template exposes event references', function () {
    $template = $this->definition->template();

    expect($template)->toBeInstanceOf(ImportTemplate::class)
        ->and($template->fileName())->toBe('template_import_participation.xlsx');

    $sheets = $template->toExport()->sheets();
    $reference = $sheets[2]->array();

    expect($sheets)->toHaveCount(3)
        ->and($sheets[0]->title())->toBe('DATA')
        ->and($sheets[1]->title())->toBe('PETUNJUK')
        ->and($sheets[2]->title())->toBe('REFERENSI')
        ->and($reference)->toContain(['Event A'])
        ->and($reference)->toContain(['Event B']);
});

test('participation normalizer reuses person logic and resolves regu', function () {
    $reguRow = regu::create(['regu' => 'Regu Satu', 'jenis_kelamin' => 'Laki - Laki']);

    $normalized = $this->definition->normalize([
        new RawImportRow(2, 1, [
            'nama' => '  Ahmad   Wijaya  ',
            'jenis_kelamin' => 'laki laki',
            'tanggal_lahir' => '2000-01-15',
            'desa' => 'Desa Part',
            'kelompok' => 'Kelompok Part',
            'jenis_peserta' => 'Pengajian Desa',
            'status_registrasi' => 'sudah',
            'regu' => 'Regu Satu',
        ], ['nama' => 'x']),
    ], participation_context($this->eventA->id));

    expect($normalized[0]->data['nama'])->toBe('Ahmad Wijaya')
        ->and($normalized[0]->data['jenis_kelamin'])->toBe('L')
        ->and($normalized[0]->data['desa_id'])->toBe($this->desa->id)
        ->and($normalized[0]->data['kelompok_id'])->toBe($this->kelompok->id)
        ->and($normalized[0]->data['regu_id'])->toBe($reguRow->id)
        ->and($normalized[0]->data['jenis_peserta'])->toBe('Pengajian Desa')
        ->and($normalized[0]->data['status_registrasi'])->toBe('sudah');
});

test('participation validator flags invalid rows', function () {
    $rows = [
        participation_normalized_row(2, ['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa' => 'Desa Part', 'desa_id' => $this->desa->id]),
        participation_normalized_row(3, ['nama' => '', 'jenis_kelamin' => 'X', 'desa' => 'Desa Tidak Ada', 'regu' => 'Regu X']),
    ];

    $summary = $this->definition->validator()->validate($rows, participation_context($this->eventA->id));

    expect($summary->totalRows)->toBe(2)
        ->and($summary->validRows)->toBe(1)
        ->and($summary->invalidRows)->toBe(1)
        ->and(collect($summary->errors)->pluck('field'))->toContain('nama')
        ->and(collect($summary->errors)->pluck('field'))->toContain('jenis_kelamin')
        ->and(collect($summary->errors)->pluck('field'))->toContain('desa')
        ->and(collect($summary->errors)->pluck('field'))->toContain('regu');
});

test('participation duplicate detector flags existing participation in target event only', function () {
    $person = Person::create(['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id]);
    Participation::create(['person_id' => $person->id, 'event_id' => $this->eventA->id, 'jenis_peserta' => 'Wajib']);

    $row = participation_normalized_row(2, ['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id]);

    // Same person, Event A → duplicate.
    $summaryA = $this->definition->duplicate([$row], participation_context($this->eventA->id));
    expect($summaryA->duplicateRows)->toBe(1);

    // Same person, Event B → NOT duplicate (new participation allowed).
    $summaryB = $this->definition->duplicate([$row], participation_context($this->eventB->id));
    expect($summaryB->duplicateRows)->toBe(0);
});

test('participation duplicate detector warns on ambiguous person identity', function () {
    Person::create(['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id]);

    // Name matches but no tanggal_lahir → ambiguous warning.
    $row = participation_normalized_row(2, ['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => null, 'desa_id' => $this->desa->id]);

    $summary = $this->definition->duplicate([$row], participation_context($this->eventA->id));

    expect($summary->duplicateRows)->toBe(0)
        ->and($summary->warnings)->toHaveCount(1);
});

test('participation committer reuses canonical service to create and skip', function () {
    $existing = Person::create(['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id]);
    Participation::create(['person_id' => $existing->id, 'event_id' => $this->eventA->id, 'jenis_peserta' => 'Wajib']);

    $rows = [
        // New person + participation.
        participation_normalized_row(2, ['nama' => 'Budi Santoso', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '1999-03-03', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]),
        // Existing participation → duplicate skip.
        participation_normalized_row(3, ['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id]),
    ];

    $commit = $this->definition->commit($rows, participation_context($this->eventA->id));

    expect($commit->summary->createdRows)->toBe(1)
        ->and($commit->summary->skippedRows)->toBe(1);

    $this->assertDatabaseHas('people', ['nama' => 'Budi Santoso']);
    $this->assertSame(2, Participation::where('event_id', $this->eventA->id)->count());
    $this->assertDatabaseHas('participations', ['person_id' => Person::where('nama', 'Budi Santoso')->first()->id, 'event_id' => $this->eventA->id]);
});

test('participation committer reports ambiguous rows as warnings', function () {
    Person::create(['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id]);

    $rows = [
        participation_normalized_row(2, ['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => null, 'desa_id' => $this->desa->id]),
    ];

    $commit = $this->definition->commit($rows, participation_context($this->eventA->id));

    expect($commit->summary->createdRows)->toBe(0)
        ->and($commit->summary->warnings)->toHaveCount(1)
        ->and($commit->createdIds)->toBe([]);
});
