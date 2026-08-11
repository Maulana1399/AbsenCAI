<?php

use App\Models\desa;
use App\Models\Event;
use App\Models\kelompok;
use App\Models\Participation;
use App\Models\Person;
use App\Services\Pengajian\PengajianImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pgi_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian Import Test',
        'slug' => 'pengajian-import-'.str()->random(6),
        'event_type' => 'pengajian',
        'status' => 'active',
    ], $overrides));
}

function pgi_desa(string $name = 'Desa Import'): desa
{
    return desa::create(['desa_asal' => $name]);
}

function pgi_kelompok(string $name, int $desaId): kelompok
{
    return kelompok::create([
        'kelompok_asal' => $name,
        'desa_id' => $desaId,
    ]);
}

function pgi_validRow(string $nama = 'Jono', string $gender = 'L', ?string $birth = '2000-01-15', string $desa = 'Desa Import', string $kelompok = 'Kelompok A'): array
{
    return [
        'nama' => $nama,
        'jenis_kelamin' => $gender,
        'tanggal_lahir' => $birth,
        'desa' => $desa,
        'kelompok' => $kelompok,
    ];
}

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

test('validate returns errors for empty nama', function () {
    $service = app(PengajianImportService::class);
    $errors = $service->validate([pgi_validRow(nama: '')]);

    expect($errors)->toHaveCount(1)
        ->and($errors[0]['errors'][0])->toContain('Nama');
});

test('validate returns errors for invalid gender', function () {
    $service = app(PengajianImportService::class);
    $errors = $service->validate([pgi_validRow(gender: 'X')]);

    expect($errors)->toHaveCount(1)
        ->and($errors[0]['errors'][0])->toContain('L atau P');
});

test('validate rejects empty tanggal_lahir and does not import', function ($birth) {
    $service = app(PengajianImportService::class);
    $desa = pgi_desa();
    pgi_kelompok('Kelompok A', $desa->id);

    $errors = $service->validate([pgi_validRow(birth: $birth)]);

    expect($errors)->toHaveCount(1)
        ->and($errors[0]['errors'][0])->toBe('Tanggal lahir wajib diisi.');

    $result = $service->import([pgi_validRow(birth: $birth)], pgi_event()->id);
    expect($result['failed_rows'])->toBe(1)
        ->and(Person::count())->toBe(0);
})->with([null, '', '   ']);

test('validate returns errors for invalid tanggal_lahir format', function () {
    $service = app(PengajianImportService::class);
    $errors = $service->validate([pgi_validRow(birth: '31/02/2000')]);

    expect($errors)->toHaveCount(1)
        ->and($errors[0]['errors'][0])->toContain('Format');
});

test('validate accepts common excel date formats for tanggal_lahir', function ($birth, $canonical) {
    $service = app(PengajianImportService::class);
    $desa = pgi_desa();
    pgi_kelompok('Kelompok A', $desa->id);

    expect($service->validate([pgi_validRow(birth: $birth)]))->toBeEmpty();

    $result = $service->import([pgi_validRow(birth: $birth)], pgi_event()->id);
    expect($result['failed_rows'])->toBe(0);

    $person = Person::where('nama', 'Jono')->first();
    expect($person)->not->toBeNull()
        ->and($person->tanggal_lahir->format('Y-m-d'))->toBe($canonical);
})->with([
    ['16/09/1999', '1999-09-16'],
    ['31-12-1985', '1985-12-31'],
    ['2000-01-01', '2000-01-01'],
    ['2000/01/15', '2000-01-15'],
    ['1999/09/16', '1999-09-16'],
]);

test('validate returns errors for empty desa', function () {
    $service = app(PengajianImportService::class);
    $errors = $service->validate([pgi_validRow(desa: '')]);

    expect($errors)->toHaveCount(1)
        ->and($errors[0]['errors'][0])->toContain('Desa');
});

test('validate passes for valid row', function () {
    $service = app(PengajianImportService::class);
    $errors = $service->validate([pgi_validRow()]);

    expect($errors)->toBeEmpty();
});

// ---------------------------------------------------------------------------
// Import — Success Cases
// ---------------------------------------------------------------------------

test('import creates person and participation', function () {
    $event = pgi_event();
    $desa = pgi_desa();
    $kelompok = pgi_kelompok('Kelompok A', $desa->id);

    $service = app(PengajianImportService::class);
    $result = $service->import([pgi_validRow()], $event->id);

    expect($result['created_persons'])->toBe(1)
        ->and($result['created_participations'])->toBe(1)
        ->and($result['failed_rows'])->toBe(0)
        ->and($result['skipped_duplicates'])->toBe(0);

    $person = Person::where('nama', 'Jono')->first();
    expect($person)->not->toBeNull()
        ->and($person->jenis_kelamin)->toBe('L')
        ->and($person->desa_id)->toBe($desa->id)
        ->and($person->kelompok_id)->toBe($kelompok->id)
        ->and($person->tanggal_lahir->format('Y-m-d'))->toBe('2000-01-15');

    $participation = Participation::where('person_id', $person->id)->first();
    expect($participation)->not->toBeNull()
        ->and($participation->event_id)->toBe($event->id)
        ->and($participation->jenis_peserta)->toBe('Pengajian Desa');
});

test('import matches existing person by nama + tanggal_lahir + desa', function () {
    $event = pgi_event();
    $desa = pgi_desa();
    pgi_kelompok('Kelompok A', $desa->id);

    $existingPerson = Person::create([
        'nama' => 'Jono',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '2000-01-15',
        'desa_id' => $desa->id,
    ]);

    $service = app(PengajianImportService::class);
    $result = $service->import([pgi_validRow()], $event->id);

    expect($result['matched_persons'])->toBe(1)
        ->and($result['created_persons'])->toBe(0)
        ->and($result['created_participations'])->toBe(1);
});

test('import matches existing person when nama differs in case and spacing', function () {
    $event = pgi_event();
    $desa = pgi_desa();
    pgi_kelompok('Kelompok A', $desa->id);

    Person::create([
        'nama' => 'Jono Bin Ahmad',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '2000-01-15',
        'desa_id' => $desa->id,
    ]);

    $service = app(PengajianImportService::class);
    $result = $service->import([pgi_validRow(nama: '  jono  bin  ahmad  ')], $event->id);

    expect($result['matched_persons'])->toBe(1);
});

test('import creates new person when nama matches but birth date differs', function () {
    $event = pgi_event();
    $desa = pgi_desa();
    pgi_kelompok('Kelompok A', $desa->id);

    Person::create([
        'nama' => 'Jono',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '1990-05-20',
        'desa_id' => $desa->id,
    ]);

    $service = app(PengajianImportService::class);
    $result = $service->import([pgi_validRow()], $event->id);

    expect($result['created_persons'])->toBe(1)
        ->and($result['matched_persons'])->toBe(0);
});

test('import skips duplicate participation for same person + event', function () {
    $event = pgi_event();
    $desa = pgi_desa();
    pgi_kelompok('Kelompok A', $desa->id);

    $service = app(PengajianImportService::class);

    // First import
    $result1 = $service->import([pgi_validRow()], $event->id);

    expect($result1['created_persons'])->toBe(1)
        ->and($result1['created_participations'])->toBe(1);

    // Second import — same data
    $result2 = $service->import([pgi_validRow()], $event->id);

    expect($result2['matched_persons'])->toBe(1)
        ->and($result2['created_participations'])->toBe(0)
        ->and($result2['skipped_duplicates'])->toBe(1);
});

test('import handles multiple rows', function () {
    $event = pgi_event();
    $desa = pgi_desa();
    pgi_kelompok('Kelompok A', $desa->id);

    $rows = [
        pgi_validRow(nama: 'Jono'),
        pgi_validRow(nama: 'Joni', birth: '1990-06-20'),
        pgi_validRow(nama: 'Jana', birth: '1985-03-10'),
    ];

    $service = app(PengajianImportService::class);
    $result = $service->import($rows, $event->id);

    expect($result['created_persons'])->toBe(3)
        ->and($result['created_participations'])->toBe(3)
        ->and($result['failed_rows'])->toBe(0);
});

test('import handles mixture of new and existing persons', function () {
    $event = pgi_event();
    $desa = pgi_desa();
    pgi_kelompok('Kelompok A', $desa->id);

    Person::create([
        'nama' => 'Existing Person',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '1990-01-01',
        'desa_id' => $desa->id,
    ]);

    $rows = [
        pgi_validRow(nama: 'Existing Person', birth: '1990-01-01'),
        pgi_validRow(nama: 'New Person', birth: '1995-05-05'),
    ];

    $service = app(PengajianImportService::class);
    $result = $service->import($rows, $event->id);

    expect($result['matched_persons'])->toBe(1)
        ->and($result['created_persons'])->toBe(1)
        ->and($result['created_participations'])->toBe(2);
});

// ---------------------------------------------------------------------------
// Import — Error Cases
// ---------------------------------------------------------------------------

test('import fails when desa not found', function () {
    $event = pgi_event();

    $service = app(PengajianImportService::class);
    $result = $service->import([pgi_validRow(desa: 'Desa Tidak Ada')], $event->id);

    expect($result['failed_rows'])->toBe(1)
        ->and($result['errors'][0]['message'])->toContain('tidak ditemukan');
});

test('import fails when kelompok not found', function () {
    $event = pgi_event();
    pgi_desa();

    $service = app(PengajianImportService::class);
    $result = $service->import([pgi_validRow(kelompok: 'Kelompok Tidak Ada')], $event->id);

    expect($result['failed_rows'])->toBe(1)
        ->and($result['errors'][0]['message'])->toContain('tidak ditemukan');
});

test('import fails when kelompok belongs to different desa', function () {
    $event = pgi_event();
    $desaA = pgi_desa('Desa A');
    $desaB = pgi_desa('Desa B');
    pgi_kelompok('Kelompok B', $desaB->id);

    $service = app(PengajianImportService::class);
    $result = $service->import([pgi_validRow(desa: 'Desa A', kelompok: 'Kelompok B')], $event->id);

    expect($result['failed_rows'])->toBe(1)
        ->and($result['errors'][0]['message'])->toContain('tidak berada di Desa');
});

test('import fails with empty nama', function () {
    $event = pgi_event();
    pgi_desa();

    $service = app(PengajianImportService::class);
    $result = $service->import([pgi_validRow(nama: '')], $event->id);

    expect($result['failed_rows'])->toBe(1)
        ->and($result['errors'][0]['message'])->toContain('Nama');
});

test('import fails with invalid gender', function () {
    $event = pgi_event();
    pgi_desa();

    $service = app(PengajianImportService::class);
    $result = $service->import([pgi_validRow(gender: 'X')], $event->id);

    expect($result['failed_rows'])->toBe(1)
        ->and($result['errors'][0]['message'])->toContain('L atau P');
});

test('import fails with invalid birth date format', function () {
    $event = pgi_event();
    pgi_desa();

    $service = app(PengajianImportService::class);
    $result = $service->import([pgi_validRow(birth: 'not-a-date')], $event->id);

    expect($result['failed_rows'])->toBe(1)
        ->and($result['errors'][0]['message'])->toContain('YYYY-MM-DD');
});

// ---------------------------------------------------------------------------
// Import — Cross-Event Separation
// ---------------------------------------------------------------------------

test('import for event A does not affect event B', function () {
    $eventA = pgi_event(['name' => 'Event A', 'slug' => 'event-a']);
    $eventB = pgi_event(['name' => 'Event B', 'slug' => 'event-b']);
    $desa = pgi_desa();
    pgi_kelompok('Kelompok A', $desa->id);

    $service = app(PengajianImportService::class);
    $service->import([pgi_validRow()], $eventA->id);

    $participationsForB = Participation::where('event_id', $eventB->id)->count();
    expect($participationsForB)->toBe(0);

    $service->import([pgi_validRow()], $eventB->id);

    expect(Participation::where('event_id', $eventA->id)->count())->toBe(1)
        ->and(Participation::where('event_id', $eventB->id)->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Import — Summary Counts
// ---------------------------------------------------------------------------

test('import summary provides correct counts for mixed scenario', function () {
    $event = pgi_event();
    $desa = pgi_desa();
    pgi_kelompok('Kelompok A', $desa->id);

    Person::create([
        'nama' => 'Existing',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '2000-01-15',
        'desa_id' => $desa->id,
    ]);

    $rows = [
        pgi_validRow(nama: 'Existing', birth: '2000-01-15'),
        pgi_validRow(nama: 'New Person', birth: '1990-05-20'),
        pgi_validRow(nama: 'New Person', birth: '1990-05-20'), // duplicate
        pgi_validRow(nama: '', birth: '1990-05-20'), // empty name = fail
    ];

    $service = app(PengajianImportService::class);
    $result = $service->import($rows, $event->id);

    expect($result['matched_persons'])->toBe(2)
        ->and($result['created_persons'])->toBe(1)
        ->and($result['created_participations'])->toBe(2)
        ->and($result['skipped_duplicates'])->toBe(1)
        ->and($result['failed_rows'])->toBe(1);
});

// ---------------------------------------------------------------------------
// Import — No Regu / No CAI Placement
// ---------------------------------------------------------------------------

test('import does not assign regu to participations', function () {
    $event = pgi_event();
    $desa = pgi_desa();
    pgi_kelompok('Kelompok A', $desa->id);

    $service = app(PengajianImportService::class);
    $result = $service->import([pgi_validRow()], $event->id);

    expect($result['created_participations'])->toBe(1);

    $participation = Participation::first();
    expect($participation->jenis_peserta)->toBe('Pengajian Desa');

    $person = $participation->person;
    expect($person->nip)->toBeNull();
});

// ---------------------------------------------------------------------------
// Import — kelompok is optional
// ---------------------------------------------------------------------------

test('import works without kelompok column', function () {
    $event = pgi_event();
    $desa = pgi_desa();

    $row = pgi_validRow(kelompok: '');

    $service = app(PengajianImportService::class);
    $result = $service->import([$row], $event->id);

    expect($result['created_persons'])->toBe(1)
        ->and($result['created_participations'])->toBe(1);

    $person = Person::first();
    expect($person->kelompok_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// Import — Case insensitive desa matching
// ---------------------------------------------------------------------------

test('import matches desa case-insensitively', function () {
    $event = pgi_event();
    pgi_desa('Desa Import');
    pgi_kelompok('Kelompok A', desa::where('desa_asal', 'Desa Import')->first()->id);

    $service = app(PengajianImportService::class);
    $result = $service->import([pgi_validRow(desa: 'desa import')], $event->id);

    expect($result['created_persons'])->toBe(1)
        ->and($result['failed_rows'])->toBe(0);
});

// ---------------------------------------------------------------------------
// Import — Desa isolation (same identity in different desa)
// ---------------------------------------------------------------------------

test('same identity in different desa does not match', function () {
    $event = pgi_event();
    $desaA = pgi_desa('Desa A');
    $desaB = pgi_desa('Desa B');
    pgi_kelompok('Kelompok A', $desaA->id);
    pgi_kelompok('Kelompok A', $desaB->id);

    Person::create([
        'nama' => 'Jono',
        'jenis_kelamin' => 'L',
        'tanggal_lahir' => '2000-01-15',
        'desa_id' => $desaA->id,
    ]);

    $service = app(PengajianImportService::class);

    $result = $service->import([pgi_validRow(desa: 'Desa B', kelompok: '')], $event->id);

    expect($result['matched_persons'])->toBe(0)
        ->and($result['created_persons'])->toBe(1)
        ->and($result['failed_rows'])->toBe(0);
});

// ---------------------------------------------------------------------------
// Import — Same kelompok name in different desa resolves correctly
// ---------------------------------------------------------------------------

test('import resolves kelompok within the correct desa when names collide', function () {
    $event = pgi_event();
    $desaA = pgi_desa('Desa Alpha');
    $desaB = pgi_desa('Desa Beta');
    $kelA = pgi_kelompok('Kelompok A', $desaA->id);
    $kelB = pgi_kelompok('Kelompok A', $desaB->id);

    $service = app(PengajianImportService::class);
    $result = $service->import(
        [pgi_validRow(desa: 'Desa Beta', kelompok: 'Kelompok A')],
        $event->id,
    );

    expect($result['created_persons'])->toBe(1)
        ->and($result['failed_rows'])->toBe(0);

    $person = Person::first();
    expect($person)->not->toBeNull()
        ->and($person->kelompok_id)->toBe($kelB->id);
});
