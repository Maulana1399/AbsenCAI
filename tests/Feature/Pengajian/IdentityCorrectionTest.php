<?php

use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\IdentityCorrectionRequest;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Models\desa;
use App\Services\Pengajian\DesaAccessService;
use App\Services\Pengajian\IdentityCorrectionService;
use Carbon\Carbon;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pgm8_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian PGM8',
        'slug' => 'pengajian-pgm8-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pgm8_desa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa PGM8 '.str()->random(4),
    ], $overrides));
}

function pgm8_grant(Event $event, desa $desa): DesaAccessGrant
{
    $now = Carbon::now();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subHour(),
        $now->copy()->addHour(),
    );
    return $result['grant'];
}

function pgm8_person(string $nama, string $gender = 'L', ?int $desaId = null, ?string $birthDate = null): Person
{
    return Person::create([
        'nama' => $nama,
        'jenis_kelamin' => $gender,
        'desa_id' => $desaId,
        'tanggal_lahir' => $birthDate,
    ]);
}

function pgm8_user(): User
{
    return User::factory()->create();
}

// ---------------------------------------------------------------------------
// SUBMISSION: 1–16
// ---------------------------------------------------------------------------

test('Valid public context dapat submit correction', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    expect($request)->toBeInstanceOf(IdentityCorrectionRequest::class);
});

test('Person tidak berubah saat submit', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');

    app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    expect($person->fresh()->nama)->toBe('Jono');
});

test('Request status pending', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');

    app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    $request = IdentityCorrectionRequest::first();
    expect($request->status)->toBe(IdentityCorrectionRequest::STATUS_PENDING);
});

test('Nama correction tersimpan benar', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');

    app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    $request = IdentityCorrectionRequest::first();
    expect($request->requested_name)->toBe('Jono Baru');
});

test('tanggal_lahir correction tersimpan benar', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');

    app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_birth_date' => '2001-02-20'],
    );

    $request = IdentityCorrectionRequest::first();
    expect($request->requested_birth_date->format('Y-m-d'))->toBe('2001-02-20');
});

test('No-change submission ditolak', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');

    expect(fn () => app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono'],
    ))->toThrow(\RuntimeException::class, 'Tidak ada perubahan');
});

test('Empty string name correction ditolak (tidak masuk filtered)', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');

    expect(fn () => app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => ''],
    ))->toThrow(\RuntimeException::class, 'Tidak ada field');
});

test('NIP correction ditolak', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');

    expect(fn () => app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono', 'nip' => 12345],
    ))->toThrow(\RuntimeException::class, 'Tidak ada perubahan');
});

test('participant_number correction ditolak', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id);

    expect(fn () => app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono', 'participant_number' => 'KP001'],
    ))->toThrow(\RuntimeException::class, 'Tidak ada perubahan');
});

test('attendance_code correction ditolak', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id);

    expect(fn () => app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono', 'attendance_code' => 'KJA-XXX'],
    ))->toThrow(\RuntimeException::class, 'Tidak ada perubahan');
});

test('Cross-Desa Person ditolak', function () {
    $event = pgm8_event();
    $desaA = pgm8_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm8_desa(['desa_asal' => 'Desa B']);
    $grant = pgm8_grant($event, $desaA);
    $personB = pgm8_person('Jono', 'L', $desaB->id);

    expect(fn () => app(IdentityCorrectionService::class)->submitFromPublicContext(
        $personB, $grant,
        ['requested_name' => 'Jono Baru'],
    ))->toThrow(\RuntimeException::class, 'tidak terdaftar di desa ini');
});

test('Person desa_id NULL ditolak', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', null);

    expect(fn () => app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    ))->toThrow(\RuntimeException::class, 'tidak memiliki desa');
});

test('Revoked grant ditolak saat submit correction', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');

    app(DesaAccessService::class)->revokeGrant($grant);

    expect(fn () => app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    ))->toThrow(\RuntimeException::class, 'tidak valid');
});

test('Expired nonce ditolak saat submit correction', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');

    $grant->update(['nonce_expires_at' => Carbon::now()->subMinute()]);

    expect(fn () => app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    ))->toThrow(\RuntimeException::class, 'tidak valid');
});

test('Duplicate identical pending request tidak menggandakan row', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');

    app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    expect(fn () => app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    ))->toThrow(\RuntimeException::class, 'identik sudah menunggu');

    expect(IdentityCorrectionRequest::count())->toBe(1);
});

test('Different field same person can have separate pending request', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');

    app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_birth_date' => '2001-02-20'],
    );

    expect(IdentityCorrectionRequest::count())->toBe(2);
});

// ---------------------------------------------------------------------------
// APPROVAL: 17–30
// ---------------------------------------------------------------------------

test('Authenticated user dapat approve correction', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect($request->fresh()->status)->toBe(IdentityCorrectionRequest::STATUS_APPROVED);
});

test('Approval update canonical Person.nama', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect($person->fresh()->nama)->toBe('Jono Baru');
});

test('Approval update canonical Person.tanggal_lahir', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_birth_date' => '2001-02-20'],
    );

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect($person->fresh()->tanggal_lahir->format('Y-m-d'))->toBe('2001-02-20');
});

test('Request menjadi approved', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect($request->fresh()->status)->toBe(IdentityCorrectionRequest::STATUS_APPROVED);
});

test('reviewed_by tersimpan', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect($request->fresh()->reviewed_by)->toBe($reviewer->id);
});

test('reviewed_at tersimpan', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect($request->fresh()->reviewed_at)->not->toBeNull();
});

test('participant_number tidak berubah setelah approval', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $participation = app(App\Services\Pengajian\PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant);

    $originalNumber = $participation->fresh()->participant_number;

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect($participation->fresh()->participant_number)->toBe($originalNumber);
});

test('attendance_code tidak berubah setelah approval', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $participation = app(App\Services\Pengajian\PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant);

    $originalCode = $participation->fresh()->attendance_code;

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect($participation->fresh()->attendance_code)->toBe($originalCode);
});

test('Participation count tidak berubah setelah approval', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    app(App\Services\Pengajian\PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant);

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect(Participation::count())->toBe(1);
});

test('EventAttendance tidak berubah setelah approval', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    app(App\Services\Pengajian\PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant);

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect(EventAttendance::count())->toBe(1);
});

test('Legacy mapped peserta nama sync setelah approval', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $legacyPeserta = \App\Models\peserta::create([
        'nama' => 'Jono',
        'nip' => random_int(10000, 99999),
        'desa_id' => $desa->id,
    ]);

    $participation = \App\Models\Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'jenis_peserta' => 'Pengajian Desa',
    ]);

    LegacyPesertaMapping::create([
        'peserta_id' => $legacyPeserta->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
        'legacy_nip' => $legacyPeserta->nip,
    ]);

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect($legacyPeserta->fresh()->nama)->toBe('Jono Baru');
});

test('Approval transactional', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    $requestId = $request->id;

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect(IdentityCorrectionRequest::find($requestId)->status)
        ->toBe(IdentityCorrectionRequest::STATUS_APPROVED);
});

test('Already approved request tidak diproses ulang', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->approve($request, $reviewer);

    expect(fn () => app(IdentityCorrectionService::class)->approve($request, $reviewer))
        ->toThrow(\RuntimeException::class, 'sudah');
});

test('Rejected request tidak dapat diapprove', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->reject($request, $reviewer);

    expect(fn () => app(IdentityCorrectionService::class)->approve($request, $reviewer))
        ->toThrow(\RuntimeException::class, 'sudah');
});

// ---------------------------------------------------------------------------
// REJECTION: 31–36
// ---------------------------------------------------------------------------

test('Reject tidak mengubah Person', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->reject($request, $reviewer);

    expect($person->fresh()->nama)->toBe('Jono');
});

test('Request menjadi rejected', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->reject($request, $reviewer);

    expect($request->fresh()->status)->toBe(IdentityCorrectionRequest::STATUS_REJECTED);
});

test('reviewed_by tersimpan saat reject', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->reject($request, $reviewer);

    expect($request->fresh()->reviewed_by)->toBe($reviewer->id);
});

test('reviewed_at tersimpan saat reject', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->reject($request, $reviewer);

    expect($request->fresh()->reviewed_at)->not->toBeNull();
});

test('rejection reason tersimpan', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->reject($request, $reviewer, 'Data sudah benar');

    expect($request->fresh()->reason)->toBe('Data sudah benar');
});

test('Already rejected request tidak diproses ulang', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $request = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    app(IdentityCorrectionService::class)->reject($request, $reviewer);

    expect(fn () => app(IdentityCorrectionService::class)->reject($request, $reviewer))
        ->toThrow(\RuntimeException::class, 'sudah');
});

// ---------------------------------------------------------------------------
// AUTHORIZATION: 37–41
// ---------------------------------------------------------------------------

test('Guest tidak dapat membuka review UI', function () {
    $response = $this->get(route('koreksi.data', absolute: false));

    $response->assertRedirect(route('login', absolute: false));
});

test('Review routes menggunakan auth convention project', function () {
    $response = $this->get(route('koreksi.data', absolute: false));

    $response->assertRedirect(route('login', absolute: false));
});

test('Authenticated user dapat mengakses review UI', function () {
    $user = pgm8_user();

    $response = $this->actingAs($user)
        ->get(route('koreksi.data', absolute: false));

    $response->assertStatus(200);
});

test('listPending mengembalikan hanya pending', function () {
    $event = pgm8_event();
    $desa = pgm8_desa();
    $grant = pgm8_grant($event, $desa);
    $person = pgm8_person('Jono', 'L', $desa->id, '2000-01-15');
    $reviewer = pgm8_user();

    $approved = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Approved'],
    );
    app(IdentityCorrectionService::class)->approve($approved, $reviewer);

    $rejected = app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Rejected'],
    );
    app(IdentityCorrectionService::class)->reject($rejected, $reviewer);

    app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Still Pending'],
    );

    $pending = app(IdentityCorrectionService::class)->listPending();

    expect($pending)->toHaveCount(1);
    expect($pending[0]->requested_name)->toBe('Still Pending');
});
