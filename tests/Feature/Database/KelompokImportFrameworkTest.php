<?php

use App\Enums\Role;
use App\Livewire\Database\Kelompok\ImportKelompok;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

class KelompokTestUploadedFile extends UploadedFile
{
    public string $name = '';

    public function __construct(string $path, string $originalName, ?string $mimeType = null, ?int $error = null, bool $test = false)
    {
        parent::__construct($path, $originalName, $mimeType, $error, $test);
        $this->name = $originalName;
    }
}

function kelompok_import_csv(string $content): KelompokTestUploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'kif_csv').'.csv';
    file_put_contents($path, $content);

    return new KelompokTestUploadedFile($path, 'kelompok.csv', 'text/csv', null, true);
}

function kelompok_import_xlsx(array $data): KelompokTestUploadedFile
{
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($data, null, 'A1');
    $path = tempnam(sys_get_temp_dir(), 'kif_xlsx').'.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

    return new KelompokTestUploadedFile($path, 'kelompok.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));
    $this->desa = desa::create(['desa_asal' => 'Desa Import']);
});

// ---------------------------------------------------------------------------
// POST route (legacy entry point → adapter → framework)
// ---------------------------------------------------------------------------

test('POST import kelompok requires the desa parameter', function () {
    $this->post(route('import.kelompok'), [
        'file' => UploadedFile::fake()->createWithContent('kelompok.csv', "kelompok\nKelompok A\n"),
    ])->assertSessionHasErrors(['desa_id']);

    $this->assertDatabaseCount('kelompoks', 0);
});

test('POST import kelompok imports under the selected desa', function () {
    $this->post(route('import.kelompok'), [
        'file' => UploadedFile::fake()->createWithContent('kelompok.csv', "kelompok\nKelompok A\nKelompok B\n"),
        'desa_id' => $this->desa->id,
    ])->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($message) => str_contains($message, '2 dibuat'));

    $this->assertDatabaseHas('kelompoks', ['kelompok_asal' => 'Kelompok A', 'desa_id' => $this->desa->id]);
    $this->assertDatabaseHas('kelompoks', ['kelompok_asal' => 'Kelompok B', 'desa_id' => $this->desa->id]);
});

test('POST import kelompok skips duplicates on second upload', function () {
    $payload = [
        'file' => UploadedFile::fake()->createWithContent('kelompok.csv', "kelompok\nKelompok A\n"),
        'desa_id' => $this->desa->id,
    ];

    $this->post(route('import.kelompok'), $payload)->assertSessionHasNoErrors();
    $this->post(route('import.kelompok'), $payload)->assertSessionHasNoErrors();

    $this->assertSame(1, kelompok::where('kelompok_asal', 'Kelompok A')->count());
});

test('POST import kelompok returns errors for missing column', function () {
    $this->post(route('import.kelompok'), [
        'file' => UploadedFile::fake()->createWithContent('bad.csv', "nama\nX\n"),
        'desa_id' => $this->desa->id,
    ])->assertSessionHasErrors(['file']);
});

test('kelompok template route downloads the generated template', function () {
    $response = $this->get(route('import.kelompok.template'));

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertDownload('template_import_kelompok.xlsx');
});

// ---------------------------------------------------------------------------
// Livewire wizard
// ---------------------------------------------------------------------------

test('wizard renders parameter select automatically from the definition', function () {
    Livewire::test(ImportKelompok::class)
        ->assertSet('step', 1)
        ->assertSee('Import Kelompok')
        ->assertSee('Desa')
        ->assertSee('Desa Import')
        ->assertSee('Preview & Validasi', false);
});

test('wizard preview requires the desa parameter', function () {
    Livewire::test(ImportKelompok::class)
        ->set('file', kelompok_import_csv("kelompok\nKelompok A\n"))
        ->call('preview')
        ->assertHasErrors('parameters.desa_id');
});

test('wizard preview shows parsed rows', function () {
    Livewire::test(ImportKelompok::class)
        ->set('parameters.desa_id', $this->desa->id)
        ->set('file', kelompok_import_csv("kelompok\nKelompok A\nKelompok B\n"))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 2)
        ->assertSet('previewRows.0.kelompok', 'Kelompok A');
});

test('wizard preview supports excel files', function () {
    Livewire::test(ImportKelompok::class)
        ->set('parameters.desa_id', $this->desa->id)
        ->set('file', kelompok_import_xlsx([
            ['kelompok'],
            ['Kelompok Excel'],
        ]))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 1);
});

test('wizard preview reports parse errors', function () {
    Livewire::test(ImportKelompok::class)
        ->set('parameters.desa_id', $this->desa->id)
        ->set('file', kelompok_import_csv("nama\nX\n"))
        ->call('preview')
        ->assertSet('step', 3)
        ->assertCount('validationErrors', 1)
        ->assertSet('validationErrors.0.row', 0);
});

test('wizard preview rejects file without data rows', function () {
    Livewire::test(ImportKelompok::class)
        ->set('parameters.desa_id', $this->desa->id)
        ->set('file', kelompok_import_csv("kelompok\n"))
        ->call('preview')
        ->assertSet('step', 3)
        ->assertCount('validationErrors', 1);
});

test('wizard import creates records and shows result', function () {
    Livewire::test(ImportKelompok::class)
        ->set('parameters.desa_id', $this->desa->id)
        ->set('file', kelompok_import_csv("kelompok\nKelompok A\nKelompok B\n"))
        ->call('preview')
        ->call('goToImport')
        ->assertSet('step', 4)
        ->assertSet('summary.total', 2)
        ->assertSet('summary.will_create', 2)
        ->call('executeImport')
        ->assertSet('step', 5)
        ->assertSet('importResult.created', 2)
        ->assertSet('importResult.failed', 0);

    $this->assertDatabaseHas('kelompoks', ['kelompok_asal' => 'Kelompok A', 'desa_id' => $this->desa->id]);
    $this->assertDatabaseHas('kelompoks', ['kelompok_asal' => 'Kelompok B', 'desa_id' => $this->desa->id]);
});

test('wizard import reports duplicates', function () {
    kelompok::create(['kelompok_asal' => 'Kelompok A', 'desa_id' => $this->desa->id]);

    Livewire::test(ImportKelompok::class)
        ->set('parameters.desa_id', $this->desa->id)
        ->set('file', kelompok_import_csv("kelompok\nKelompok A\nKelompok Baru\n"))
        ->call('preview')
        ->assertSet('step', 2)
        ->call('goToImport')
        ->assertSet('summary.duplicate', 1)
        ->assertSet('summary.will_create', 1)
        ->call('executeImport')
        ->assertSet('step', 5)
        ->assertSet('importResult.created', 1)
        ->assertSet('importResult.duplicate', 1);

    $this->assertSame(1, kelompok::where('kelompok_asal', 'Kelompok A')->count());
});

test('wizard reset clears state and returns to upload', function () {
    Livewire::test(ImportKelompok::class)
        ->set('parameters.desa_id', $this->desa->id)
        ->set('file', kelompok_import_csv("kelompok\nKelompok A\n"))
        ->call('preview')
        ->call('goToImport')
        ->call('resetImport')
        ->assertSet('step', 1)
        ->assertSet('file', null)
        ->assertSet('parameters', [])
        ->assertSet('previewRows', [])
        ->assertSet('validationErrors', [])
        ->assertSet('importResult', []);
});
