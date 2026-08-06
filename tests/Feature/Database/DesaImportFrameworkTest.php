<?php

use App\Enums\Role;
use App\Livewire\Database\Desa\ImportDesa;
use App\Models\desa;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

class DesaTestUploadedFile extends UploadedFile
{
    public string $name = '';

    public function __construct(string $path, string $originalName, ?string $mimeType = null, ?int $error = null, bool $test = false)
    {
        parent::__construct($path, $originalName, $mimeType, $error, $test);
        $this->name = $originalName;
    }
}

function desa_import_csv(string $content): DesaTestUploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'dif_csv').'.csv';
    file_put_contents($path, $content);

    return new DesaTestUploadedFile($path, 'desa.csv', 'text/csv', null, true);
}

function desa_import_xlsx(array $data): DesaTestUploadedFile
{
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($data, null, 'A1');
    $path = tempnam(sys_get_temp_dir(), 'dif_xlsx').'.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

    return new DesaTestUploadedFile($path, 'desa.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));
});

// ---------------------------------------------------------------------------
// POST route (legacy entry point → adapter → framework)
// ---------------------------------------------------------------------------

test('POST import desa imports via the framework adapter', function () {
    $this->post(route('import.desa'), [
        'file' => UploadedFile::fake()->createWithContent('desa.csv', "desa\nDesa Route\n"),
    ])->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($message) => str_contains($message, '1 dibuat'));

    $this->assertDatabaseHas('desas', ['desa_asal' => 'Desa Route']);
});

test('POST import desa skips duplicates on second upload', function () {
    $csv = UploadedFile::fake()->createWithContent('desa.csv', "desa\nDesa Route\n");

    $this->post(route('import.desa'), ['file' => $csv])->assertSessionHasNoErrors();
    $this->post(route('import.desa'), ['file' => $csv])->assertSessionHasNoErrors();

    $this->assertSame(1, desa::where('desa_asal', 'Desa Route')->count());
});

test('POST import desa returns errors for a file without the required column', function () {
    $this->post(route('import.desa'), [
        'file' => UploadedFile::fake()->createWithContent('bad.csv', "nama\nDesa Route\n"),
    ])->assertSessionHasErrors(['file']);

    $this->assertDatabaseCount('desas', 0);
});

test('template route downloads the generated desa template', function () {
    $response = $this->get(route('import.desa.template'));

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertDownload('template_import_desa.xlsx');
});

// ---------------------------------------------------------------------------
// Livewire wizard
// ---------------------------------------------------------------------------

test('wizard renders upload step initially', function () {
    Livewire::test(ImportDesa::class)
        ->assertSet('step', 1)
        ->assertSee('Import Desa')
        ->assertSee('Preview & Validasi');
});

test('wizard preview shows parsed rows', function () {
    Livewire::test(ImportDesa::class)
        ->set('file', desa_import_csv("desa\nDesa Satu\nDesa Dua\n"))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 2)
        ->assertSet('previewRows.0.desa', 'Desa Satu');
});

test('wizard preview supports excel files', function () {
    Livewire::test(ImportDesa::class)
        ->set('file', desa_import_xlsx([
            ['desa'],
            ['Desa Excel'],
        ]))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 1);
});

test('wizard preview reports missing column error', function () {
    Livewire::test(ImportDesa::class)
        ->set('file', desa_import_csv("nama\nDesa Satu\n"))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertCount('validationErrors', 1)
        ->assertSet('validationErrors.0.row', 0);
});

test('wizard preview rejects empty file', function () {
    Livewire::test(ImportDesa::class)
        ->set('file', desa_import_csv("desa\n"))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertCount('previewRows', 0);
});

test('wizard import creates desa records and shows result', function () {
    Livewire::test(ImportDesa::class)
        ->set('file', desa_import_csv("desa\nDesa Satu\nDesa Dua\n"))
        ->call('preview')
        ->call('executeImport')
        ->assertSet('step', 3)
        ->assertSet('importResult.created', 2)
        ->assertSet('importResult.failed', 0)
        ->assertSet('importResult.total', 2);

    $this->assertDatabaseHas('desas', ['desa_asal' => 'Desa Satu']);
    $this->assertDatabaseHas('desas', ['desa_asal' => 'Desa Dua']);
});

test('wizard import reports duplicates', function () {
    desa::create(['desa_asal' => 'Desa Satu']);

    Livewire::test(ImportDesa::class)
        ->set('file', desa_import_csv("desa\nDesa Satu\nDesa Baru\n"))
        ->call('preview')
        ->call('executeImport')
        ->assertSet('step', 3)
        ->assertSet('importResult.created', 1)
        ->assertSet('importResult.duplicate', 1);

    $this->assertSame(1, desa::where('desa_asal', 'Desa Satu')->count());
    $this->assertDatabaseHas('desas', ['desa_asal' => 'Desa Baru']);
});

test('wizard import cannot run without a clean preview', function () {
    Livewire::test(ImportDesa::class)
        ->set('file', desa_import_csv("nama\nDesa Satu\n"))
        ->call('preview')
        ->call('executeImport')
        ->assertSet('step', 2)
        ->assertCount('validationErrors', 1);

    $this->assertDatabaseCount('desas', 0);
});

test('wizard reset returns to upload step and clears state', function () {
    Livewire::test(ImportDesa::class)
        ->set('file', desa_import_csv("desa\nDesa Satu\n"))
        ->call('preview')
        ->call('resetImport')
        ->assertSet('step', 1)
        ->assertSet('file', null)
        ->assertSet('previewRows', [])
        ->assertSet('validationErrors', [])
        ->assertSet('importResult', []);
});

test('wizard uploadError fills the upload error message', function () {
    Livewire::test(ImportDesa::class)
        ->call('uploadError')
        ->assertSet('uploadError', fn ($message) => str_contains($message, 'Upload file gagal'));
});
