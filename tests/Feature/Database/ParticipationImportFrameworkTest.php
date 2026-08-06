<?php

use App\Enums\Role;
use App\Livewire\Registrasi\ImportParticipation;
use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Support\ActiveEventContext;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

class ParticipationTestUploadedFile extends UploadedFile
{
    public string $name = '';

    public function __construct(string $path, string $originalName, ?string $mimeType = null, ?int $error = null, bool $test = false)
    {
        parent::__construct($path, $originalName, $mimeType, $error, $test);
        $this->name = $originalName;
    }
}

function participation_import_csv(string $content): ParticipationTestUploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'part_csv').'.csv';
    file_put_contents($path, $content);

    return new ParticipationTestUploadedFile($path, 'participation.csv', 'text/csv', null, true);
}

function participation_import_xlsx(array $data): ParticipationTestUploadedFile
{
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($data, null, 'A1');
    $path = tempnam(sys_get_temp_dir(), 'part_xlsx').'.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

    return new ParticipationTestUploadedFile($path, 'participation.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));
    $this->desa = desa::create(['desa_asal' => 'Desa Import']);
    kelompok::create(['kelompok_asal' => 'Kelompok A', 'desa_id' => $this->desa->id]);
    $this->event = Event::create(['name' => 'Event Import', 'slug' => 'event-import-'.str()->random(5), 'event_type' => 'pengajian', 'status' => 'active']);
    app(ActiveEventContext::class)->set($this->event);
});

test('participation template route downloads the generated template', function () {
    $response = $this->get(route('import.participation.template', ['event' => $this->event]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertDownload('template_import_participation.xlsx');
});

test('wizard defaults event parameter to active event', function () {
    Livewire::test(ImportParticipation::class)
        ->assertSet('step', 1)
        ->assertSet('parameters.event_id', (string) $this->event->id)
        ->assertSee('Import Participation')
        ->assertSee('Event Import')
        ->assertSee('Preview & Validasi', false);
});

test('wizard preview shows parsed rows', function () {
    Livewire::test(ImportParticipation::class)
        ->set('file', participation_import_csv("nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,L,2000-01-15,Desa Import,Kelompok A\nBudi Santoso,P,1999-03-03,Desa Import,Kelompok A\n"))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 2)
        ->assertSet('previewRows.0.nama', 'Ahmad Wijaya');
});

test('wizard preview supports excel files', function () {
    Livewire::test(ImportParticipation::class)
        ->set('file', participation_import_xlsx([
            ['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok'],
            ['Budi Santoso', 'L', '1999-03-03', 'Desa Import', 'Kelompok A'],
        ]))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 1);
});

test('wizard import creates persons and participations', function () {
    Livewire::test(ImportParticipation::class)
        ->set('file', participation_import_csv("nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,L,2000-01-15,Desa Import,Kelompok A\nBudi Santoso,P,1999-03-03,Desa Import,Kelompok A\n"))
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
    $this->assertSame(2, Participation::where('event_id', $this->event->id)->count());
});

test('wizard import reports duplicates for existing participation', function () {
    $person = Person::create(['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id]);
    Participation::create(['person_id' => $person->id, 'event_id' => $this->event->id, 'jenis_peserta' => 'Wajib']);

    Livewire::test(ImportParticipation::class)
        ->set('file', participation_import_csv("nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,L,2000-01-15,Desa Import,Kelompok A\nBudi Santoso,L,1999-03-03,Desa Import,Kelompok A\n"))
        ->call('preview')
        ->assertSet('step', 2)
        ->call('goToImport')
        ->assertSet('summary.duplicate', 1)
        ->assertSet('summary.will_create', 1)
        ->call('executeImport')
        ->assertSet('step', 5)
        ->assertSet('importResult.created', 1)
        ->assertSet('importResult.duplicate', 1);

    $this->assertSame(2, Participation::where('event_id', $this->event->id)->count());
    $this->assertSame(1, Person::where('nama', 'Budi Santoso')->count());
});

test('wizard import reports ambiguous warning without tanggal lahir', function () {
    Person::create(['nama' => 'Ahmad Wijaya', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $this->desa->id]);

    Livewire::test(ImportParticipation::class)
        ->set('file', participation_import_csv("nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,L,,Desa Import,Kelompok A\n"))
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->call('goToImport')
        ->assertSet('summary.warning', 1)
        ->call('executeImport')
        ->assertSet('step', 5)
        ->assertSet('importResult.warning', 1);
});

test('wizard reset clears state', function () {
    Livewire::test(ImportParticipation::class)
        ->set('file', participation_import_csv("nama,jenis_kelamin,tanggal_lahir,desa,kelompok\nAhmad Wijaya,L,2000-01-15,Desa Import,Kelompok A\n"))
        ->call('preview')
        ->call('goToImport')
        ->call('resetImport')
        ->assertSet('step', 1)
        ->assertSet('file', null)
        ->assertSet('previewRows', [])
        ->assertSet('validationErrors', [])
        ->assertSet('importResult', []);
});
