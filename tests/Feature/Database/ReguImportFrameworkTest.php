<?php

use App\Enums\Role;
use App\Livewire\Database\Regu\ImportRegu;
use App\Models\regu;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

class ReguTestUploadedFile extends UploadedFile
{
    public string $name = '';

    public function __construct(string $path, string $originalName, ?string $mimeType = null, ?int $error = null, bool $test = false)
    {
        parent::__construct($path, $originalName, $mimeType, $error, $test);
        $this->name = $originalName;
    }
}

function regu_import_csv(string $content): ReguTestUploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'rif_csv').'.csv';
    file_put_contents($path, $content);

    return new ReguTestUploadedFile($path, 'regu.csv', 'text/csv', null, true);
}

function regu_import_xlsx(array $data): ReguTestUploadedFile
{
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($data, null, 'A1');
    $path = tempnam(sys_get_temp_dir(), 'rif_xlsx').'.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

    return new ReguTestUploadedFile($path, 'regu.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));
});

// ---------------------------------------------------------------------------
// POST route (legacy entry point → adapter → framework)
// ---------------------------------------------------------------------------

test('POST import regu imports and normalizes gender', function () {
    $this->post(route('import.regu'), [
        'file' => UploadedFile::fake()->createWithContent('regu.csv', "regu,jenis_kelamin\nMerah,laki laki\nBiru,Perempuan\n"),
    ])->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($message) => str_contains($message, '2 dibuat'));

    $this->assertDatabaseHas('regus', ['regu' => 'Merah', 'jenis_kelamin' => 'Laki - Laki']);
    $this->assertDatabaseHas('regus', ['regu' => 'Biru', 'jenis_kelamin' => 'Perempuan']);
});

test('POST import regu fails when jenis kelamin is invalid', function () {
    $this->post(route('import.regu'), [
        'file' => UploadedFile::fake()->createWithContent('regu.csv', "regu,jenis_kelamin\nMerah,Unknown\n"),
    ])->assertSessionHasErrors(['jenis_kelamin']);

    $this->assertDatabaseCount('regus', 0);
});

test('POST import regu skips duplicates by unique regu name', function () {
    regu::create(['regu' => 'Merah', 'jenis_kelamin' => 'Laki - Laki']);

    $this->post(route('import.regu'), [
        'file' => UploadedFile::fake()->createWithContent('regu.csv', "regu,jenis_kelamin\nMerah,Laki - Laki\nBiru,Perempuan\n"),
    ])->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($message) => str_contains($message, '1 duplikat'));

    $this->assertSame(1, regu::where('regu', 'Merah')->count());
    $this->assertDatabaseHas('regus', ['regu' => 'Biru', 'jenis_kelamin' => 'Perempuan']);
});

test('regu template route downloads the generated template', function () {
    $response = $this->get(route('import.regu.template'));

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertDownload('template_import_regu.xlsx');
});

// ---------------------------------------------------------------------------
// Livewire wizard (reusable ImportWizardBase)
// ---------------------------------------------------------------------------

test('wizard renders without parameters and shows columns', function () {
    Livewire::test(ImportRegu::class)
        ->assertSet('step', 1)
        ->assertSee('Import Regu')
        ->assertSee('regu')
        ->assertSee('Preview & Validasi', false);
});

test('wizard preview shows parsed rows with normalized gender', function () {
    Livewire::test(ImportRegu::class)
        ->set('file', regu_import_csv("regu,jenis_kelamin\nMerah,laki laki\nBiru,Perempuan\n"))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 2)
        ->assertSet('previewRows.0.regu', 'Merah');
});

test('wizard preview supports excel files', function () {
    Livewire::test(ImportRegu::class)
        ->set('file', regu_import_xlsx([
            ['regu', 'jenis_kelamin'],
            ['Regu Excel', 'Laki - Laki'],
        ]))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 1);
});

test('wizard preview reports parse errors for missing column', function () {
    Livewire::test(ImportRegu::class)
        ->set('file', regu_import_csv("nama\nX\n"))
        ->call('preview')
        ->assertSet('step', 3)
        ->assertCount('validationErrors', 1)
        ->assertSet('validationErrors.0.row', 0);
});

test('wizard import creates records and shows result', function () {
    Livewire::test(ImportRegu::class)
        ->set('file', regu_import_csv("regu,jenis_kelamin\nMerah,Laki - Laki\nBiru,Perempuan\n"))
        ->call('preview')
        ->call('goToImport')
        ->assertSet('step', 4)
        ->assertSet('summary.total', 2)
        ->assertSet('summary.will_create', 2)
        ->call('executeImport')
        ->assertSet('step', 5)
        ->assertSet('importResult.created', 2)
        ->assertSet('importResult.failed', 0);

    $this->assertDatabaseHas('regus', ['regu' => 'Merah', 'jenis_kelamin' => 'Laki - Laki']);
    $this->assertDatabaseHas('regus', ['regu' => 'Biru', 'jenis_kelamin' => 'Perempuan']);
});

test('wizard import reports duplicates', function () {
    regu::create(['regu' => 'Merah', 'jenis_kelamin' => 'Laki - Laki']);

    Livewire::test(ImportRegu::class)
        ->set('file', regu_import_csv("regu,jenis_kelamin\nMerah,Laki - Laki\nBiru,Perempuan\n"))
        ->call('preview')
        ->call('goToImport')
        ->assertSet('summary.duplicate', 1)
        ->call('executeImport')
        ->assertSet('step', 5)
        ->assertSet('importResult.created', 1)
        ->assertSet('importResult.duplicate', 1);

    $this->assertSame(1, regu::where('regu', 'Merah')->count());
});

test('wizard reset clears state and returns to upload', function () {
    Livewire::test(ImportRegu::class)
        ->set('file', regu_import_csv("regu,jenis_kelamin\nMerah,Laki - Laki\n"))
        ->call('preview')
        ->call('goToImport')
        ->call('resetImport')
        ->assertSet('step', 1)
        ->assertSet('file', null)
        ->assertSet('previewRows', [])
        ->assertSet('validationErrors', [])
        ->assertSet('importResult', []);
});
