<?php

use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Services\Import\Adapters\Peserta\PesertaImportDefinition;
use App\Services\Import\Contracts\ImportTemplate;
use App\Services\Import\DTO\ImportContext;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

function pst_context(string $mode = 'execute'): ImportContext
{
    return new ImportContext(
        type: 'peserta',
        mode: $mode,
        options: ['rows' => []],
        definitionKey: 'peserta',
    );
}

beforeEach(function () {
    $this->definition = app(PesertaImportDefinition::class);
    $this->event = Event::create(['name' => 'Event Peserta', 'slug' => 'event-peserta-'.str()->random(5), 'status' => 'active']);
    app(ActiveEventContext::class)->set($this->event);
    $this->desa = desa::create(['desa_asal' => 'Desa Peserta']);
    $this->kelompok = kelompok::create(['kelompok_asal' => 'Kelompok Peserta', 'desa_id' => $this->desa->id]);
});

test('peserta definition exposes metadata', function () {
    expect($this->definition->displayName())->toBe('Import Peserta')
        ->and($this->definition->description())->toContain('peserta')
        ->and($this->definition->icon())->not->toBe('')
        ->and($this->definition->key())->toBe('peserta')
        ->and($this->definition->parameters())->toBe([])
        ->and($this->definition->columns())->toHaveKeys(['nama', 'jenis_kelamin', 'kelompok', 'desa', 'jenis_peserta'])
        ->and($this->definition->rules())->toHaveKeys(['nama', 'jenis_kelamin']);
});

test('peserta definition template is available as metadata', function () {
    $template = $this->definition->template();

    expect($template)->toBeInstanceOf(ImportTemplate::class)
        ->and($template->fileName())->toBe('template_peserta.xlsx')
        ->and(count($template->toExport()->sheets()))->toBe(3);
});

test('peserta parser reads csv rows and wraps array source', function () {
    $rows = $this->definition->parser()->parse([
        ['nama' => 'Peserta A', 'jenis_kelamin' => 'Laki - Laki', 'kelompok' => 'Kelompok Peserta', 'desa' => 'Desa Peserta'],
    ], pst_context());

    expect($rows)->toHaveCount(1)
        ->and($rows[0]->rowNumber)->toBe(2)
        ->and($rows[0]->raw['nama'])->toBe('Peserta A');
});

test('peserta validator flags empty nama and gender', function () {
    $rows = $this->definition->normalize($this->definition->parser()->parse([
        ['nama' => '', 'jenis_kelamin' => ''],
    ], pst_context()), pst_context());

    $summary = $this->definition->validator()->validate($rows, pst_context());

    expect($summary->invalidRows)->toBe(1)
        ->and(collect($summary->errors)->pluck('message'))->toContain('Nama wajib diisi.')
        ->and(collect($summary->errors)->pluck('message'))->toContain('Jenis kelamin wajib diisi.');
});

test('peserta committer creates full canonical record set via RegistrationService', function () {
    regu::create(['regu' => 'Regu Laki', 'jenis_kelamin' => 'Laki - Laki']);

    $rows = $this->definition->normalize($this->definition->parser()->parse([
        ['nama' => 'Peserta Baru', 'jenis_kelamin' => 'Laki - Laki', 'kelompok' => 'Kelompok Peserta', 'desa' => 'Desa Peserta'],
    ], pst_context()), pst_context());

    $commit = $this->definition->commit($rows, pst_context());

    expect($commit->createdIds)->toHaveCount(1)
        ->and($commit->failedRows)->toBe([])
        ->and(Person::count())->toBe(1)
        ->and(peserta::count())->toBe(1)
        ->and(Participation::where('event_id', $this->event->id)->count())->toBe(1)
        ->and(LegacyPesertaMapping::count())->toBe(1)
        ->and(Participation::first()->regu_id)->not->toBeNull();
});

test('peserta committer skips rows without nama', function () {
    $rows = $this->definition->normalize($this->definition->parser()->parse([
        ['nama' => '', 'jenis_kelamin' => 'Laki - Laki'],
    ], pst_context()), pst_context());

    $commit = $this->definition->commit($rows, pst_context());

    expect($commit->createdIds)->toBe([])
        ->and($commit->failedRows)->toBe([])
        ->and(peserta::count())->toBe(0);
});

test('peserta committer records failed row when registration throws', function () {
    Person::create(['nama' => 'Duplikat', 'jenis_kelamin' => 'L', 'desa_id' => $this->desa->id]);
    peserta::create(['nama' => 'Duplikat', 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id, 'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI]);
    app(ActiveEventContext::class)->set($this->event);
    Participation::create(['person_id' => Person::where('nama', 'Duplikat')->first()->id, 'event_id' => $this->event->id, 'jenis_peserta' => 'Wajib']);

    $rows = $this->definition->normalize($this->definition->parser()->parse([
        ['nama' => 'Duplikat', 'jenis_kelamin' => 'Laki - Laki', 'kelompok' => 'Kelompok Peserta', 'desa' => 'Desa Peserta'],
    ], pst_context()), pst_context());

    $commit = $this->definition->commit($rows, pst_context());

    expect($commit->failedRows)->toHaveCount(1)
        ->and($commit->createdIds)->toBe([]);
});
