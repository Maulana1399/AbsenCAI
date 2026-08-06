<?php

use App\Enums\Role;
use App\Livewire\Pengajian\Admin\ImportMassal;
use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Services\Import\Adapters\ImportAdapter;
use App\Services\Import\DTO\ImportContext;
use App\Support\ActiveEventContext;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/**
 * PARITY TEST — behavior-preserving refactor.
 * The golden Pengajian behavior (counters, messages, DB writes, participation
 * format) is asserted through the NEW framework path. These values were
 * captured from the legacy implementation and must remain IDENTICAL.
 */
class ParityUploadedFile extends UploadedFile
{
    public string $name = '';

    public function __construct(string $path, string $originalName, ?string $mimeType = null, ?int $error = null, bool $test = false)
    {
        parent::__construct($path, $originalName, $mimeType, $error, $test);
        $this->name = $originalName;
    }
}

function parity_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Parity Event',
        'slug' => 'parity-event-'.str()->random(6),
        'event_type' => 'pengajian',
        'status' => 'active',
    ], $overrides));
}

function parity_desa(string $name = 'Desa Import'): desa
{
    return desa::create(['desa_asal' => $name]);
}

function parity_kelompok(string $name, int $desaId): kelompok
{
    return kelompok::create(['kelompok_asal' => $name, 'desa_id' => $desaId]);
}

function parity_rows(array $rows): array
{
    return array_merge(
        [['nama', 'jenis_kelamin', 'tanggal_lahir', 'desa', 'kelompok']],
        $rows,
    );
}

function parity_validRow(string $nama = 'Jono', string $gender = 'L', string $birth = '2000-01-15', string $desa = 'Desa Import', string $kelompok = 'Kelompok A'): array
{
    return [
        'nama' => $nama,
        'jenis_kelamin' => $gender,
        'tanggal_lahir' => $birth,
        'desa' => $desa,
        'kelompok' => $kelompok,
    ];
}

function parity_csv(array $rows): ParityUploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'parity_csv').'.csv';
    $handle = fopen($path, 'w');
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    return new ParityUploadedFile($path, 'parity.csv', 'text/csv', null, true);
}

function parity_xlsx(array $data): ParityUploadedFile
{
    $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($data, null, 'A1');
    $path = tempnam(sys_get_temp_dir(), 'parity_xlsx').'.xlsx';
    (new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

    return new ParityUploadedFile($path, 'parity.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

function parity_frameworkCommit(int $eventId, array $rows): array
{
    $context = new ImportContext(
        type: 'pengajian',
        eventId: $eventId,
        mode: 'execute',
        options: ['rows' => $rows],
        definitionKey: 'pengajian',
    );

    $result = app(ImportAdapter::class)->commit('pengajian', $rows, $context);

    return [
        'metrics' => $result->commit?->metrics ?? [],
        'failedRows' => $result->commit?->failedRows ?? [],
    ];
}

beforeEach(function () {
    $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));
});

test('parity — mixed scenario counters and DB writes identical', function () {
    $event = parity_event();
    $desa = parity_desa();
    parity_kelompok('Kelompok A', $desa->id);
    Person::create(['nama' => 'Existing', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $desa->id]);

    $rows = [
        parity_validRow(nama: 'Existing', birth: '2000-01-15'),
        parity_validRow(nama: 'New Person', birth: '1990-05-20'),
        parity_validRow(nama: 'New Person', birth: '1990-05-20'), // duplicate
        parity_validRow(nama: '', birth: '1990-05-20'), // fail
    ];

    $out = parity_frameworkCommit($event->id, $rows);

    expect($out['metrics']['matched_persons'])->toBe(2)
        ->and($out['metrics']['created_persons'])->toBe(1)
        ->and($out['metrics']['created_participations'])->toBe(2)
        ->and($out['metrics']['skipped_duplicates'])->toBe(1)
        ->and($out['metrics']['failed_rows'])->toBe(1)
        ->and($out['failedRows'])->toHaveCount(1)
        ->and($out['failedRows'][0]['message'])->toBe('Nama wajib diisi.');

    // DB result identical.
    expect(Person::count())->toBe(2)
        ->and(Participation::where('event_id', $event->id)->count())->toBe(2)
        ->and(Participation::first()->jenis_peserta)->toBe('Pengajian Desa')
        ->and(Participation::first()->regu_id)->toBeNull()
        ->and(Participation::first()->person->nip)->toBeNull();
});

test('parity — case and spacing name matching identical', function () {
    $event = parity_event();
    $desa = parity_desa();
    parity_kelompok('Kelompok A', $desa->id);
    Person::create(['nama' => 'Jono Bin Ahmad', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $desa->id]);

    $out = parity_frameworkCommit($event->id, [parity_validRow(nama: '  jono  bin  ahmad  ')]);

    expect($out['metrics']['matched_persons'])->toBe(1)
        ->and($out['metrics']['created_persons'])->toBe(0)
        ->and(Person::count())->toBe(1);
});

test('parity — desa matched case-insensitively', function () {
    $event = parity_event();
    $desa = parity_desa('Desa Import');
    parity_kelompok('Kelompok A', $desa->id);

    $out = parity_frameworkCommit($event->id, [parity_validRow(desa: 'desa import')]);

    expect($out['metrics']['created_persons'])->toBe(1)
        ->and($out['metrics']['failed_rows'])->toBe(0)
        ->and(Person::first()->desa_id)->toBe($desa->id);
});

test('parity — same identity in different desa does not match', function () {
    $event = parity_event();
    $desaA = parity_desa('Desa A');
    $desaB = parity_desa('Desa B');
    parity_kelompok('Kelompok A', $desaA->id);
    parity_kelompok('Kelompok A', $desaB->id);
    Person::create(['nama' => 'Jono', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $desaA->id]);

    $out = parity_frameworkCommit($event->id, [parity_validRow(desa: 'Desa B', kelompok: '')]);

    expect($out['metrics']['matched_persons'])->toBe(0)
        ->and($out['metrics']['created_persons'])->toBe(1)
        ->and($out['metrics']['failed_rows'])->toBe(0);
});

test('parity — kelompok collision resolves within correct desa', function () {
    $event = parity_event();
    $desaA = parity_desa('Desa Alpha');
    $desaB = parity_desa('Desa Beta');
    $kelA = parity_kelompok('Kelompok A', $desaA->id);
    $kelB = parity_kelompok('Kelompok A', $desaB->id);

    $out = parity_frameworkCommit($event->id, [parity_validRow(desa: 'Desa Beta', kelompok: 'Kelompok A')]);

    expect($out['metrics']['created_persons'])->toBe(1)
        ->and($out['metrics']['failed_rows'])->toBe(0)
        ->and(Person::first()->kelompok_id)->toBe($kelB->id);
});

test('parity — works without kelompok (kelompok_id null)', function () {
    $event = parity_event();
    parity_desa('Desa Import');

    $out = parity_frameworkCommit($event->id, [parity_validRow(kelompok: '')]);

    expect($out['metrics']['created_persons'])->toBe(1)
        ->and($out['metrics']['failed_rows'])->toBe(0)
        ->and(Person::first()->kelompok_id)->toBeNull();
});

test('parity — participation identifiers keep golden format', function () {
    $event = parity_event();
    parity_desa('Desa Import');
    parity_kelompok('Kelompok A', desa::first()->id);

    $out = parity_frameworkCommit($event->id, [parity_validRow(gender: 'l')]);

    expect($out['metrics']['created_participations'])->toBe(1);

    $participation = Participation::first();
    expect($participation->participant_number)->toMatch('/^KL\d{3}$/')
        ->and($participation->attendance_code)->toMatch('/^KJA-[A-Z0-9]{8}$/')
        ->and($participation->jenis_peserta)->toBe('Pengajian Desa')
        ->and($participation->person->jenis_kelamin)->toBe('L');
});

test('parity — same data re-imported skips duplicate participation', function () {
    $event = parity_event();
    parity_desa('Desa Import');
    parity_kelompok('Kelompok A', desa::first()->id);

    $rows = [parity_validRow()];

    $first = parity_frameworkCommit($event->id, $rows);
    expect($first['metrics']['created_participations'])->toBe(1);

    $second = parity_frameworkCommit($event->id, $rows);
    expect($second['metrics']['matched_persons'])->toBe(1)
        ->and($second['metrics']['created_participations'])->toBe(0)
        ->and($second['metrics']['skipped_duplicates'])->toBe(1);

    expect(Participation::where('event_id', $event->id)->count())->toBe(1);
});

test('parity — wizard preview reports validation error like golden', function () {
    $event = parity_event();
    app(ActiveEventContext::class)->set($event);
    $desa = parity_desa('Desa Import');
    parity_kelompok('Kelompok A', $desa->id);

    $csv = parity_csv(parity_rows([
        parity_validRow(nama: ''),
    ]));

    Livewire::test(ImportMassal::class)
        ->set('file', $csv)
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 1)
        ->assertCount('validationErrors', 1)
        ->assertSet('validationErrors.0.row', 2)
        ->assertSet('validationErrors.0.errors.0', 'Nama wajib diisi.');
});

test('parity — wizard import result identical through framework', function () {
    $event = parity_event();
    app(ActiveEventContext::class)->set($event);
    $desa = parity_desa('Desa Import');
    parity_kelompok('Kelompok A', $desa->id);
    Person::create(['nama' => 'Existing', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2000-01-15', 'desa_id' => $desa->id]);

    $csv = parity_csv(parity_rows([
        parity_validRow(nama: 'Existing', birth: '2000-01-15'),
        parity_validRow(nama: 'New Person', birth: '1990-05-20'),
    ]));

    Livewire::test(ImportMassal::class)
        ->set('file', $csv)
        ->call('preview')
        ->assertSet('step', 2)
        ->assertHasNoErrors()
        ->assertCount('previewRows', 2)
        ->call('executeImport')
        ->assertSet('step', 3)
        ->assertSet('importResult.created_persons', 1)
        ->assertSet('importResult.matched_persons', 1)
        ->assertSet('importResult.created_participations', 2)
        ->assertSet('importResult.skipped_duplicates', 0)
        ->assertSet('importResult.failed_rows', 0)
        ->assertCount('importResult.errors', 0);

    expect(Person::count())->toBe(2)
        ->and(Participation::where('event_id', $event->id)->count())->toBe(2);
});
