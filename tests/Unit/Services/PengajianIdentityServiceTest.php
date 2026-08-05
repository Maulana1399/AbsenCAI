<?php

use App\Models\desa;
use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\IdentityCorrectionRequest;
use App\Models\Participation;
use App\Models\Person;
use App\Services\Pengajian\DesaAccessService;
use App\Services\Pengajian\PengajianAttendanceService;
use App\Services\Pengajian\PengajianIdentityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pgm5_makeEvent(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian Desa',
        'slug' => 'pengajian-desa-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pgm5_makeDesa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Test '.str()->random(4),
    ], $overrides));
}

function pgm5_makeGrant(Event $event, desa $desa): DesaAccessGrant
{
    $now = Carbon::now();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subHour(),
        $now->copy()->addHour(),
    );

    return $result['grant'];
}

function pgm5_makePerson(string $nama, string $gender = 'L', ?int $desaId = null, ?string $birthDate = null): Person
{
    return Person::create([
        'nama' => $nama,
        'jenis_kelamin' => $gender,
        'desa_id' => $desaId,
        'tanggal_lahir' => $birthDate,
    ]);
}

// ---------------------------------------------------------------------------
// Search — desa scope
// ---------------------------------------------------------------------------

test('search Desa A hanya return Person Desa A', function () {
    $event = pgm5_makeEvent();
    $desaA = pgm5_makeDesa(['desa_asal' => 'Desa A']);
    $grantA = pgm5_makeGrant($event, $desaA);

    pgm5_makePerson('Jono', 'L', $desaA->id);
    pgm5_makePerson('Joni', 'L', $desaA->id);
    pgm5_makePerson('Jojo', 'L', null);

    $results = app(PengajianIdentityService::class)->searchPersons($grantA, 'Jono');

    expect($results)->toHaveCount(1)
        ->and($results[0]['nama'])->toBe('Jono');
});

test('Person Desa B tidak muncul pada search Desa A', function () {
    $event = pgm5_makeEvent();
    $desaA = pgm5_makeDesa(['desa_asal' => 'Desa A']);
    $desaB = pgm5_makeDesa(['desa_asal' => 'Desa B']);
    $grantA = pgm5_makeGrant($event, $desaA);

    pgm5_makePerson('Jono Desa A', 'L', $desaA->id);
    pgm5_makePerson('Jono Desa B', 'L', $desaB->id);

    $results = app(PengajianIdentityService::class)->searchPersons($grantA, 'Jono');

    expect($results)->toHaveCount(1)
        ->and($results[0]['nama'])->toBe('Jono Desa A');
});

// ---------------------------------------------------------------------------
// Search — validation & limits
// ---------------------------------------------------------------------------

test('query terlalu pendek return empty', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $grant = pgm5_makeGrant($event, $desa);
    pgm5_makePerson('Jono', 'L', $desa->id);

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Jo');

    expect($results)->toHaveCount(0);
});

test('query dua karakter return empty', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $grant = pgm5_makeGrant($event, $desa);

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'ab');

    expect($results)->toHaveCount(0);
});

test('search result dibatasi jumlahnya', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $grant = pgm5_makeGrant($event, $desa);

    foreach (range(1, 25) as $i) {
        pgm5_makePerson('Orang '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'L', $desa->id);
    }

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Orang');

    expect($results)->toHaveCount(PengajianIdentityService::MAX_RESULTS);
});

// ---------------------------------------------------------------------------
// Search — privacy: tidak expose PII
// ---------------------------------------------------------------------------

test('search result tidak expose NIP', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $grant = pgm5_makeGrant($event, $desa);
    $person = pgm5_makePerson('Jono', 'L', $desa->id);
    $person->update(['nip' => 12345]);

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Jono');

    expect($results[0])->not->toHaveKey('nip');
});

test('search result tidak expose attendance_code', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $grant = pgm5_makeGrant($event, $desa);
    pgm5_makePerson('Jono', 'L', $desa->id);

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Jono');

    expect($results[0])->not->toHaveKey('attendance_code');
});

test('search result tidak expose participant_number event lain', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $grant = pgm5_makeGrant($event, $desa);
    pgm5_makePerson('Jono', 'L', $desa->id);

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Jono');

    expect($results[0])->not->toHaveKey('participant_number');
});

test('search result tidak tampilkan tanggal_lahir full', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $grant = pgm5_makeGrant($event, $desa);
    pgm5_makePerson('Jono', 'L', $desa->id, '2000-01-15');

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Jono');

    expect($results[0])->not->toHaveKey('tanggal_lahir')
        ->and($results[0]['birth_date_masked'])->not->toContain('2000');
});

test('search result hanya field minimum', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $grant = pgm5_makeGrant($event, $desa);
    pgm5_makePerson('Jono', 'L', $desa->id);

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Jono');

    expect($results[0])->toHaveKeys(['id', 'nama', 'birth_date_masked', 'has_birth_date']);
});

// ---------------------------------------------------------------------------
// Birth date verification
// ---------------------------------------------------------------------------

test('Person dengan tanggal_lahir dapat diverifikasi', function () {
    $person = pgm5_makePerson('Jono', 'L', null, '2000-01-15');

    $result = app(PengajianIdentityService::class)->verifyBirthDate($person, '2000-01-15');

    expect($result)->toBeTrue();
});

test('Wrong birth date gagal verification', function () {
    $person = pgm5_makePerson('Jono', 'L', null, '2000-01-15');

    $result = app(PengajianIdentityService::class)->verifyBirthDate($person, '2000-01-16');

    expect($result)->toBeFalse();
});

test('Person tanpa tanggal_lahir fallback ke false pada verification', function () {
    $person = pgm5_makePerson('Jono', 'L', null, null);

    $result = app(PengajianIdentityService::class)->verifyBirthDate($person, '2000-01-15');

    expect($result)->toBeFalse();
});

// ---------------------------------------------------------------------------
// Correction request — submission
// ---------------------------------------------------------------------------

test('Correction request dapat dibuat', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $person = pgm5_makePerson('Jono', 'L', $desa->id, '2000-01-01');

    $request = app(PengajianIdentityService::class)->submitCorrection(
        $person,
        [
            'requested_name' => 'Jono Sukirman',
            'requested_birth_date' => '2000-02-01',
            'reason' => 'Nama kurang lengkap',
        ],
        $event->id,
        $desa->id,
    );

    expect($request)->toBeInstanceOf(IdentityCorrectionRequest::class)
        ->and($request->person_id)->toBe($person->id)
        ->and($request->requested_name)->toBe('Jono Sukirman')
        ->and($request->requested_birth_date->format('Y-m-d'))->toBe('2000-02-01')
        ->and($request->status)->toBe(IdentityCorrectionRequest::STATUS_PENDING)
        ->and($request->submitted_at)->not->toBeNull();
});

test('Correction request tidak mengubah Person.nama', function () {
    $desa = pgm5_makeDesa();
    $person = pgm5_makePerson('Jono', 'L', $desa->id);

    app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['requested_name' => 'Jono Baru'],
        desaId: $desa->id,
    );

    expect($person->fresh()->nama)->toBe('Jono');
});

test('Correction request tidak mengubah Person.tanggal_lahir', function () {
    $desa = pgm5_makeDesa();
    $person = pgm5_makePerson('Jono', 'L', $desa->id, '2000-01-01');

    app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['requested_birth_date' => '2000-02-01'],
        desaId: $desa->id,
    );

    expect($person->fresh()->tanggal_lahir->format('Y-m-d'))->toBe('2000-01-01');
});

test('Correction request tidak mengubah Person.desa_id', function () {
    $desa = pgm5_makeDesa();
    $desaLain = pgm5_makeDesa(['desa_asal' => 'Desa Lain']);
    $person = pgm5_makePerson('Jono', 'L', $desa->id);

    app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['requested_desa_id' => $desaLain->id],
        desaId: $desa->id,
    );

    expect($person->fresh()->desa_id)->toBe($desa->id);
});

// ---------------------------------------------------------------------------
// Correction request — duplicate prevention
// ---------------------------------------------------------------------------

test('Duplicate pending correction ditolak', function () {
    $desa = pgm5_makeDesa();
    $person = pgm5_makePerson('Jono', 'L', $desa->id);

    app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['requested_name' => 'Jono Satu'],
        desaId: $desa->id,
    );

    expect(fn () => app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['requested_name' => 'Jono Dua'],
        desaId: $desa->id,
    ))->toThrow(\RuntimeException::class, 'Sudah ada permintaan koreksi yang pending');
});

test('Duplicate ditolak hanya untuk pending, approved tetap bisa baru', function () {
    $desa = pgm5_makeDesa();
    $person = pgm5_makePerson('Jono', 'L', $desa->id);

    $first = app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['requested_name' => 'Jono Satu'],
        desaId: $desa->id,
    );
    $first->update(['status' => IdentityCorrectionRequest::STATUS_APPROVED]);

    $second = app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['requested_name' => 'Jono Dua'],
        desaId: $desa->id,
    );

    expect($second->status)->toBe(IdentityCorrectionRequest::STATUS_PENDING);
});

// ---------------------------------------------------------------------------
// Correction request — cross-desa ditolak
// ---------------------------------------------------------------------------

test('Cross-Desa correction request ditolak', function () {
    $desaA = pgm5_makeDesa(['desa_asal' => 'Desa A']);
    $desaB = pgm5_makeDesa(['desa_asal' => 'Desa B']);
    $personDesaA = pgm5_makePerson('Jono A', 'L', $desaA->id);

    expect(fn () => app(PengajianIdentityService::class)->submitCorrection(
        $personDesaA,
        ['requested_name' => 'Jono B'],
        desaId: $desaB->id,
    ))->toThrow(\RuntimeException::class, 'tidak terdaftar di desa');

    expect(IdentityCorrectionRequest::count())->toBe(0);
});

test('Person tanpa desa tidak bisa submit correction', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $person = pgm5_makePerson('Jono', 'L', null);

    expect(fn () => app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['requested_name' => 'Jono Baru'],
        $event->id,
        $desa->id,
    ))->toThrow(\RuntimeException::class, 'tidak memiliki desa');

    expect(IdentityCorrectionRequest::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Public context — desa_id tidak auto-assign
// ---------------------------------------------------------------------------

test('Public context tidak auto-assign Person.desa_id', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $grant = pgm5_makeGrant($event, $desa);
    $person = pgm5_makePerson('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonPublicContext(
        $person, $grant,
    );

    expect($person->fresh()->desa_id)->toBe($desa->id);
});

test('Public context Person tanpa desa_id ditolak', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $grant = pgm5_makeGrant($event, $desa);
    $person = pgm5_makePerson('Jono', 'L', null);

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonPublicContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class, 'tidak memiliki desa');

    expect(EventAttendance::count())->toBe(0)
        ->and(Participation::count())->toBe(0);
});

test('Public context Person desa_id tidak match ditolak', function () {
    $event = pgm5_makeEvent();
    $desaA = pgm5_makeDesa(['desa_asal' => 'Desa A']);
    $desaB = pgm5_makeDesa(['desa_asal' => 'Desa B']);
    $grant = pgm5_makeGrant($event, $desaA);
    $person = pgm5_makePerson('Jono', 'L', $desaB->id);

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonPublicContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class, 'tidak terdaftar di desa ini');

    expect(EventAttendance::count())->toBe(0)
        ->and(Participation::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Operator-assisted context tetap bisa auto-assign (PGM.4 regression)
// ---------------------------------------------------------------------------

test('Operator-assisted masih bisa auto-assign Person.desa_id', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $person = pgm5_makePerson('Jono', 'L', null);

    app(PengajianAttendanceService::class)->attendPerson(
        $person, $event->id, $desa->id,
    );

    expect($person->fresh()->desa_id)->toBe($desa->id)
        ->and(EventAttendance::count())->toBe(1)
        ->and(Participation::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// IdentityCorrectionRequest metadata berisi snapshot
// ---------------------------------------------------------------------------

test('Correction request metadata berisi snapshot current values', function () {
    $desa = pgm5_makeDesa();
    $person = pgm5_makePerson('Jono', 'L', $desa->id, '2000-01-01');

    $request = app(PengajianIdentityService::class)->submitCorrection(
        $person,
        [
            'requested_name' => 'Jono Baru',
            'requested_birth_date' => '2001-01-01',
        ],
        desaId: $desa->id,
    );

    expect($request->metadata)->toHaveKey('current_name', 'Jono')
        ->and($request->metadata)->toHaveKey('current_birth_date', '2000-01-01')
        ->and($request->metadata)->toHaveKey('current_desa_id', $desa->id);
});

// ---------------------------------------------------------------------------
// Grant tanpa desa_id ditolak — in-memory model (not persisted)
// since DB schema requires desa_id NOT NULL
// ---------------------------------------------------------------------------

test('Grant tanpa desa_id throw exception saat search', function () {
    $event = pgm5_makeEvent();
    $grant = new DesaAccessGrant([
        'event_id' => $event->id,
        'desa_id' => null,
        'token_hash' => 'hash',
        'token_prefix' => 'BAD',
        'valid_from' => Carbon::now()->subHour(),
        'valid_until' => Carbon::now()->addHour(),
        'nonce' => Str::random(32),
        'nonce_expires_at' => Carbon::now()->addDay(),
    ]);

    expect(fn () => app(PengajianIdentityService::class)->searchPersons($grant, 'Jono'))
        ->toThrow(\RuntimeException::class, 'tidak memiliki desa');
});

test('Service search tetap defensive meskipun DB guard desa_id NOT NULL', function () {
    $event = pgm5_makeEvent();
    $desa = pgm5_makeDesa();
    $grant = pgm5_makeGrant($event, $desa);
    pgm5_makePerson('Jono', 'L', $desa->id);

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Jono');

    expect($results)->toHaveCount(1);
    $grant->desa_id = null;

    expect(fn () => app(PengajianIdentityService::class)->searchPersons($grant, 'Jono'))
        ->toThrow(\RuntimeException::class, 'tidak memiliki desa');
});
