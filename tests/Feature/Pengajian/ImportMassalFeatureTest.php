<?php

use App\Enums\Role;
use App\Livewire\Pengajian\Admin\ImportMassal;
use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

class NamedUploadedFile extends UploadedFile
{
    public string $name = '';

    public function __construct(string $path, string $originalName, ?string $mimeType = null, ?int $error = null, bool $test = false)
    {
        parent::__construct($path, $originalName, $mimeType, $error, $test);
        $this->name = $originalName;
    }
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function imf_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Import Massal Feature',
        'slug' => 'import-massal-feature-'.str()->random(6),
        'event_type' => 'pengajian',
        'status' => 'active',
    ], $overrides));
}

function imf_desa(string $name = 'Desa Import'): desa
{
    return desa::create(['desa_asal' => $name]);
}

function imf_kelompok(string $name, int $desaId): kelompok
{
    return kelompok::create([
        'kelompok_asal' => $name,
        'desa_id' => $desaId,
    ]);
}

function imf_rows(array $rows): array
{
    return array_merge(
        [['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok']],
        $rows,
    );
}

function imf_validXlsx(): UploadedFile
{
    return imf_xlsx(imf_rows([
        ['Jono', 'L', '2000-01-15', 'Desa Import', 'Kelompok A'],
    ]));
}

function imf_xlsx(array $data): UploadedFile
{
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($data, null, 'A1');
    $path = tempnam(sys_get_temp_dir(), 'imf_xlsx').'.xlsx';
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

    return new NamedUploadedFile(
        $path,
        'import.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true,
    );
}

function imf_validCsv(): UploadedFile
{
    return imf_csv(imf_rows([
        ['Jono', 'L', '2000-01-15', 'Desa Import', 'Kelompok A'],
    ]));
}

function imf_csv(array $data): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'imf_csv').'.csv';
    $handle = fopen($path, 'w');

    foreach ($data as $row) {
        fputcsv($handle, $row);
    }

    fclose($handle);

    return new NamedUploadedFile($path, 'import.csv', 'text/csv', null, true);
}

function imf_mountComponent()
{
    $event = app(ActiveEventContext::class)->requireCurrent();

    return Livewire::test(ImportMassal::class)
        ->assertSet('eventName', $event->name);
}

beforeEach(function () {
    $this->user = User::factory()->create(['role' => Role::SuperAdmin]);
    $this->actingAs($this->user);

    $event = imf_event();
    app(ActiveEventContext::class)->set($event);

    $desa = imf_desa();
    imf_kelompok('Kelompok A', $desa->id);
});

// ---------------------------------------------------------------------------
// 1. Upload xlsx valid
// ---------------------------------------------------------------------------

test('upload file xlsx valid diterima', function () {
    imf_mountComponent()
        ->set('file', imf_validXlsx())
        ->assertSet('file', fn ($file) => $file !== null);
});

// ---------------------------------------------------------------------------
// 2. Upload csv valid
// ---------------------------------------------------------------------------

test('upload file csv valid diterima', function () {
    imf_mountComponent()
        ->set('file', imf_validCsv())
        ->assertSet('file', fn ($file) => $file !== null);
});

// ---------------------------------------------------------------------------
// 3. Preview berhasil (xlsx + csv)
// ---------------------------------------------------------------------------

test('preview berhasil untuk file xlsx', function () {
    imf_mountComponent()
        ->set('file', imf_validXlsx())
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertSet('validationErrors', [])
        ->assertCount('previewRows', 1);
});

test('preview berhasil untuk file csv', function () {
    imf_mountComponent()
        ->set('file', imf_validCsv())
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertSet('validationErrors', [])
        ->assertCount('previewRows', 1);
});

test('preview menampilkan summary validasi sukses', function () {
    imf_mountComponent()
        ->set('file', imf_validCsv())
        ->call('preview')
        ->assertSet('step', 2)
        ->assertSet('validationErrors', [])
        ->assertCount('previewRows', 1);
});

// ---------------------------------------------------------------------------
// 4. Import berhasil (menyimpan Person + Participation)
// ---------------------------------------------------------------------------

test('import berhasil untuk file xlsx', function () {
    imf_mountComponent()
        ->set('file', imf_validXlsx())
        ->call('preview')
        ->call('executeImport')
        ->assertSet('step', 3);

    expect(Person::count())->toBe(1)
        ->and(Participation::count())->toBe(1);

    $person = Person::where('nama', 'Jono')->first();
    expect($person)->not->toBeNull()
        ->and($person->jenis_kelamin)->toBe('L')
        ->and($person->tanggal_lahir->format('Y-m-d'))->toBe('2000-01-15');

    $participation = Participation::where('person_id', $person->id)->first();
    expect($participation)->not->toBeNull()
        ->and($participation->event_id)->toBe(app(ActiveEventContext::class)->requireCurrent()->id)
        ->and($participation->jenis_peserta)->toBe('Pengajian Desa');
});

test('import berhasil untuk file csv', function () {
    imf_mountComponent()
        ->set('file', imf_validCsv())
        ->call('preview')
        ->call('executeImport')
        ->assertSet('step', 3);

    expect(Person::count())->toBe(1)
        ->and(Participation::count())->toBe(1);
});

test('import menampilkan jumlah berhasil dan gagal', function () {
    imf_mountComponent()
        ->set('file', imf_csv(imf_rows([
            ['Jono', 'L', '2000-01-15', 'Desa Import', 'Kelompok A'],
            ['Joni', 'L', '1990-06-20', 'Desa Import', 'Kelompok A'],
            ['Gagal', 'L', '1990-06-20', 'Desa Tidak Ada', 'Kelompok A'],
        ])))
        ->call('preview')
        ->call('executeImport')
        ->assertSet('step', 3)
        ->assertSet('importResult.created_persons', 2)
        ->assertSet('importResult.created_participations', 2)
        ->assertSet('importResult.failed_rows', 1)
        ->assertCount('importResult.errors', 1);

    expect(Person::count())->toBe(2)
        ->and(Participation::count())->toBe(2);
});

// ---------------------------------------------------------------------------
// 5. File invalid ditolak
// ---------------------------------------------------------------------------

test('file invalid (bukan csv/xlsx/xls) ditolak', function () {
    $file = new NamedUploadedFile(
        tempnam(sys_get_temp_dir(), 'imf_bad'),
        'import.pdf',
        'application/pdf',
        null,
        true,
    );

    imf_mountComponent()
        ->set('file', $file)
        ->call('preview')
        ->assertHasErrors('file');
});

test('file tanpa kolom wajib ditolak dengan pesan error', function () {
    $csv = imf_csv([
        ['foo', 'bar', 'baz'],
        ['a', 'b', 'c'],
    ]);

    imf_mountComponent()
        ->set('file', $csv)
        ->call('preview')
        ->assertSet('step', 2)
        ->assertCount('validationErrors', 1)
        ->assertSet('validationErrors.0.errors.0', fn ($message) => str_contains($message, 'Kolom wajib tidak ditemukan'));
});

// ---------------------------------------------------------------------------
// 6. Validation error tampil
// ---------------------------------------------------------------------------

test('validation error per baris tampil di preview', function () {
    imf_mountComponent()
        ->set('file', imf_csv(imf_rows([
            ['', 'L', '2000-01-15', 'Desa Import', 'Kelompok A'],
        ])))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertCount('validationErrors', 1)
        ->assertCount('previewRows', 1);
});

// ---------------------------------------------------------------------------
// 7. Duplicate tetap berjalan
// ---------------------------------------------------------------------------

test('import ulang dengan data sama: duplicate dilewati, tidak dobel', function () {
    $component = imf_mountComponent()
        ->set('file', imf_validCsv())
        ->call('preview')
        ->call('executeImport');

    expect(Participation::count())->toBe(1);

    imf_mountComponent()
        ->set('file', imf_validCsv())
        ->call('preview')
        ->call('executeImport');

    expect(Person::count())->toBe(1)
        ->and(Participation::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// 8. Empty file ditolak
// ---------------------------------------------------------------------------

test('file kosong ditolak', function () {
    $csv = imf_csv([['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok']]);

    imf_mountComponent()
        ->set('file', $csv)
        ->call('preview')
        ->assertSet('step', 2)
        ->assertCount('previewRows', 0)
        ->assertCount('validationErrors', 1);
});

test('preview tanpa file ditolak oleh validasi', function () {
    imf_mountComponent()
        ->call('preview')
        ->assertHasErrors('file');
});

// ---------------------------------------------------------------------------
// 9. UI & Reset
// ---------------------------------------------------------------------------

test('halaman merender langkah upload dengan input file dan tombol preview', function () {
    imf_mountComponent()
        ->assertSee('Import Massal Peserta Pengajian')
        ->assertSee('Preview & Validasi', false)
        ->assertSeeHtml('wire:model="file"')
        ->assertSet('step', 1);
});

test('uploadError mengisi pesan error upload', function () {
    imf_mountComponent()
        ->call('uploadError')
        ->assertSet('uploadError', fn ($message) => str_contains($message, 'Upload file gagal'));
});

test('resetImport mengembalikan ke langkah awal dan menghapus state', function () {
    imf_mountComponent()
        ->set('file', imf_validCsv())
        ->call('preview')
        ->call('resetImport')
        ->assertSet('step', 1)
        ->assertSet('file', null)
        ->assertSet('previewRows', [])
        ->assertSet('validationErrors', [])
        ->assertSet('importResult', []);
});
