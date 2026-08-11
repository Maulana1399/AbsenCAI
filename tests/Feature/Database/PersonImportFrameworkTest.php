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

/**
 * xlsx dengan worksheet yang XML-nya mendeklarasikan dimension penuh
 * (A1:E1048576) + trailing empty rows sampai r="1048576" — bentuk file
 * production yang sebelumnya memicu memory exhaustion via Worksheet::toArray().
 */
function person_import_huge_dates_xlsx(): PersonTestUploadedFile
{
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet;
    $ws = $spreadsheet->getActiveSheet();
    $ws->fromArray([
        ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'],
        ['Ahmad', 'L', null, 'Desa Import', 'Kelompok A'],
        ['Budi', 'P', '', 'Desa Import', 'Kelompok A'],
    ], null, 'A1');

    $serial = PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel('1999-09-16');
    $ws->getCell('C2')->setValue($serial);
    $ws->getCell('C2')->getStyle()->getNumberFormat()->setFormatCode('dd/mm/yyyy');

    $path = tempnam(sys_get_temp_dir(), 'pif_huge').'.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

    $zip = new ZipArchive;
    $zip->open($path);
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $sheetXml = preg_replace('/<dimension[^\/]*\/>/', '<dimension ref="A1:E1048576"/>', $sheetXml, 1);

    $trailing = '';

    foreach ([1048572, 1048573, 1048574, 1048575, 1048576] as $row) {
        $cells = '';

        foreach (['A', 'B', 'C', 'D', 'E'] as $column) {
            $cells .= '<c r="'.$column.$row.'" t="inlineStr"><is><t></t></is></c>';
        }

        $trailing .= '<row r="'.$row.'" spans="1:5">'.$cells.'</row>';
    }

    $sheetXml = str_replace('</sheetData>', $trailing.'</sheetData>', $sheetXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();

    return new PersonTestUploadedFile($path, 'person-huge.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
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

function person_import_dates_xlsx(): PersonTestUploadedFile
{
    $ss = new PhpOffice\PhpSpreadsheet\Spreadsheet;
    $ws = $ss->getActiveSheet();
    $ws->fromArray([
        ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'],
        ['Ahmad', 'L', null, 'Desa Import', 'Kelompok A'],
        ['Budi', 'P', null, 'Desa Import', 'Kelompok A'],
        ['Cici', 'L', null, 'Desa Import', 'Kelompok A'],
        ['Dodi', 'L', null, 'Desa Import', 'Kelompok A'],
        ['Fajar', 'L', '2000-01-01', 'Desa Import', 'Kelompok A'],
    ], null, 'A1');

    $serial = fn (string $date) => PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($date);

    $ws->getCell('C2')->setValue($serial('1999-09-16'));
    $ws->getCell('C2')->getStyle()->getNumberFormat()->setFormatCode('dd/mm/yyyy');
    $ws->getCell('C3')->setValue($serial('1985-12-31'));
    $ws->getCell('C3')->getStyle()->getNumberFormat()->setFormatCode('dd-mm-yyyy');
    $ws->getCell('C4')->setValue($serial('2000-01-01'));
    $ws->getCell('C4')->getStyle()->getNumberFormat()->setFormatCode('yyyy-mm-dd');
    $ws->getCell('C5')->setValue($serial('1990-06-20'));

    $path = tempnam(sys_get_temp_dir(), 'pif_dates').'.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save($path);

    return new PersonTestUploadedFile($path, 'person.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

test('excel native date formats pass validation and normalize preview/commit', function () {
    Livewire::test(ImportPerson::class)
        ->set('file', person_import_dates_xlsx())
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 5)
        ->assertSet('previewRows.0.tanggal_lahir', '1999-09-16') // dd/mm/yyyy cell
        ->assertSet('previewRows.1.tanggal_lahir', '1985-12-31') // dd-mm-yyyy cell
        ->assertSet('previewRows.2.tanggal_lahir', '2000-01-01') // yyyy-mm-dd cell
        ->assertSet('previewRows.3.tanggal_lahir', '1990-06-20') // serial cell
        ->assertSet('previewRows.4.tanggal_lahir', '2000-01-01') // yyyy-mm-dd string
        ->call('goToImport')
        ->call('executeImport')
        ->assertSet('step', 5)
        ->assertSet('importResult.created', 5)
        ->assertSet('importResult.failed', 0);

    expect(Person::where('nama', 'Ahmad')->first()->tanggal_lahir->format('Y-m-d'))->toBe('1999-09-16')
        ->and(Person::where('nama', 'Budi')->first()->tanggal_lahir->format('Y-m-d'))->toBe('1985-12-31')
        ->and(Person::where('nama', 'Cici')->first()->tanggal_lahir->format('Y-m-d'))->toBe('2000-01-01')
        ->and(Person::where('nama', 'Dodi')->first()->tanggal_lahir->format('Y-m-d'))->toBe('1990-06-20')
        ->and(Person::where('nama', 'Fajar')->first()->tanggal_lahir->format('Y-m-d'))->toBe('2000-01-01');
});

test('excel dd/mm/yyyy dates no longer produce validation errors', function () {
    Livewire::test(ImportPerson::class)
        ->set('file', person_import_dates_xlsx())
        ->call('preview')
        ->assertHasNoErrors()
        ->assertSet('validationErrors', []);
});

test('excel empty tanggal lahir is rejected and not imported', function () {
    $ss = new PhpOffice\PhpSpreadsheet\Spreadsheet;
    $ss->getActiveSheet()->fromArray([
        ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'],
        ['Euis', 'P', '', 'Desa Import', 'Kelompok A'],
    ], null, 'A1');
    $path = tempnam(sys_get_temp_dir(), 'pif_empty').'.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save($path);

    Livewire::test(ImportPerson::class)
        ->set('file', new PersonTestUploadedFile($path, 'person-empty.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true))
        ->call('preview')
        ->assertSet('step', 3)
        ->assertCount('previewRows', 1)
        ->assertSet('validationErrors.0.row', 2)
        ->assertSet('validationErrors.0.errors.0', 'Tanggal lahir wajib diisi.');

    expect(Person::count())->toBe(0);
});

test('person import parses an xlsx with a 1,048,576-row dimension without memory exhaustion', function () {
    Livewire::test(ImportPerson::class)
        ->set('file', person_import_huge_dates_xlsx())
        ->call('preview')
        ->assertSet('step', 3)
        ->assertCount('previewRows', 2)
        ->assertSet('previewRows.0.tanggal_lahir', '1999-09-16') // native dd/mm/yyyy cell → canonical
        ->assertSet('validationErrors.0.row', 3)
        ->assertSet('validationErrors.0.errors.0', 'Tanggal lahir wajib diisi.');

    expect(Person::count())->toBe(0);
});
