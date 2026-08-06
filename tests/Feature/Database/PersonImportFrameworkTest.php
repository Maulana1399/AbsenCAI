<?php

use App\Enums\Role;
use App\Livewire\MasterData\Person\ImportPerson;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

class PersonTestUploadedFile extends UploadedFile
{
    public string $name = '';

    public function __construct(string $path, string $originalName, ?string $mimeType = null, ?int $error = null, bool $test = false)
    {
        parent::__construct($path, $originalName, $mimeType, $error, $test);
        $this->name = $originalName;
    }
}

function person_import_csv(string $content): PersonTestUploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'pif_csv').'.csv';
    file_put_contents($path, $content);

    return new PersonTestUploadedFile($path, 'person.csv', 'text/csv', null, true);
}

function person_import_xlsx(array $data): PersonTestUploadedFile
{
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($data, null, 'A1');
    $path = tempnam(sys_get_temp_dir(), 'pif_xlsx').'.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

    return new PersonTestUploadedFile($path, 'person.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));
    $this->desa = desa::create(['desa_asal' => 'Desa Import']);
    $this->kelompok = kelompok::create(['kelompok_asal' => 'Kelompok A', 'desa_id' => $this->desa->id]);
});

// ---------------------------------------------------------------------------
// POST route (legacy entry point → adapter → framework)
// ---------------------------------------------------------------------------

test('POST import person imports and normalizes gender', function () {
    $this->post(route('import.person'), [
        'file' => UploadedFile::fake()->createWithContent('person.csv', "nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,laki laki,2000-01-15,Desa Import,Kelompok A\nSiti Rahmawati,Perempuan,1998-06-20,Desa Import,Kelompok A\n"),
    ])->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($message) => str_contains($message, '2 dibuat'));

    $ahmad = Person::where('nama', 'Ahmad Wijaya')->first();
    expect($ahmad)->not->toBeNull()
        ->and($ahmad->jenis_kelamin)->toBe('L')
        ->and($ahmad->tanggal_lahir->format('Y-m-d'))->toBe('2000-01-15')
        ->and($ahmad->desa_id)->toBe($this->desa->id)
        ->and($ahmad->kelompok_id)->toBe($this->kelompok->id);
});

test('POST import person skips existing duplicates by identity', function () {
    Person::create(['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id]);

    $this->post(route('import.person'), [
        'file' => UploadedFile::fake()->createWithContent('person.csv', "nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,L,2000-01-15,Desa Import,Kelompok A\nBudi Santoso,L,1999-03-03,Desa Import,Kelompok A\n"),
    ])->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn ($message) => str_contains($message, '1 dibuat'));

    $this->assertSame(1, Person::where('nama', 'Ahmad Wijaya')->count());
    $this->assertSame(1, Person::where('nama', 'Budi Santoso')->count());
});

test('POST import person returns errors for invalid identity', function () {
    $this->post(route('import.person'), [
        'file' => UploadedFile::fake()->createWithContent('person.csv', "nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,Unknown,2000-01-15,Desa Tidak Ada,Kelompok A\n"),
    ])->assertSessionHasErrors(['jenis_kelamin', 'desa']);

    $this->assertDatabaseCount('people', 0);
});

test('person template route downloads the generated template', function () {
    $response = $this->get(route('import.person.template'));

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertDownload('template_import_person.xlsx');
});

// ---------------------------------------------------------------------------
// Livewire wizard (reusable ImportWizardBase)
// ---------------------------------------------------------------------------

test('wizard renders without parameters and shows columns', function () {
    Livewire::test(ImportPerson::class)
        ->assertSet('step', 1)
        ->assertSee('Import Person')
        ->assertSee('Preview & Validasi', false);
});

test('wizard preview shows parsed rows with normalized gender', function () {
    Livewire::test(ImportPerson::class)
        ->set('file', person_import_csv("nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,laki laki,2000-01-15,Desa Import,Kelompok A\n"))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 1)
        ->assertSet('previewRows.0.nama', 'Ahmad Wijaya')
        ->assertSet('previewRows.0.jenis_kelamin', 'L');
});

test('wizard preview supports excel files', function () {
    Livewire::test(ImportPerson::class)
        ->set('file', person_import_xlsx([
            ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'],
            ['Budi Santoso', 'L', '1999-03-03', 'Desa Import', 'Kelompok A'],
        ]))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 1);
});

test('wizard preview reports validation errors', function () {
    Livewire::test(ImportPerson::class)
        ->set('file', person_import_csv("nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,Unknown,not-a-date,Desa Tidak Ada,Kelompok A\n"))
        ->call('preview')
        ->assertSet('step', 3)
        ->assertCount('validationErrors', 4)
        ->assertSet('validationErrors.0.row', 2);
});

test('wizard import creates records and shows result', function () {
    Livewire::test(ImportPerson::class)
        ->set('file', person_import_csv("nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,L,2000-01-15,Desa Import,Kelompok A\nBudi Santoso,P,1999-03-03,Desa Import,Kelompok A\n"))
        ->call('preview')
        ->call('goToImport')
        ->assertSet('step', 4)
        ->assertSet('summary.total', 2)
        ->assertSet('summary.will_create', 2)
        ->call('executeImport')
        ->assertSet('step', 5)
        ->assertSet('importResult.created', 2)
        ->assertSet('importResult.failed', 0);

    $this->assertSame(2, Person::count());
});

test('wizard import reports duplicates', function () {
    Person::create(['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id]);

    Livewire::test(ImportPerson::class)
        ->set('file', person_import_csv("nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,L,2000-01-15,Desa Import,Kelompok A\nBudi Santoso,L,1999-03-03,Desa Import,Kelompok A\n"))
        ->call('preview')
        ->call('goToImport')
        ->assertSet('summary.duplicate', 1)
        ->call('executeImport')
        ->assertSet('step', 5)
        ->assertSet('importResult.created', 1)
        ->assertSet('importResult.duplicate', 1);

    $this->assertSame(1, Person::where('nama', 'Ahmad Wijaya')->count());
});

test('wizard reset clears state and returns to upload', function () {
    Livewire::test(ImportPerson::class)
        ->set('file', person_import_csv("nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,L,2000-01-15,Desa Import,Kelompok A\n"))
        ->call('preview')
        ->call('goToImport')
        ->call('resetImport')
        ->assertSet('step', 1)
        ->assertSet('file', null)
        ->assertSet('previewRows', [])
        ->assertSet('validationErrors', [])
        ->assertSet('importResult', []);
});
