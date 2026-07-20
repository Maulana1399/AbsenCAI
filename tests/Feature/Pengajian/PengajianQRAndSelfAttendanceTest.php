<?php

use App\Livewire\Pengajian\DesaDashboard;
use App\Livewire\Pengajian\SelfAttendance;
use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Participation;
use App\Models\Person;
use App\Models\desa;
use App\Services\Pengajian\DesaAccessService;
use App\Services\Pengajian\PengajianAttendanceService;
use App\Services\Pengajian\PengajianIdentityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pgm6_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian Desa Agustus 2026',
        'slug' => 'pengajian-agustus-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pgm6_desa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Test '.str()->random(4),
    ], $overrides));
}

function pgm6_grant(Event $event, desa $desa, ?User $user = null): array
{
    $now = Carbon::now();
    return app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subHour(),
        $now->copy()->addHour(),
        $user,
    );
}

function pgm6_person(string $nama, string $gender = 'L', ?int $desaId = null, ?string $birthDate = null): Person
{
    return Person::create([
        'nama' => $nama,
        'jenis_kelamin' => $gender,
        'desa_id' => $desaId,
        'tanggal_lahir' => $birthDate,
    ]);
}

// ===========================================================================
// QR ACCESS — Nonce validation
// ===========================================================================

test('valid nonce membuka public attendance page', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];

    $response = $this->get(route('pengajian.hadir', ['nonce' => $grant->nonce]));

    $response->assertStatus(200);
    $response->assertSee($event->name);
    $response->assertSee($desa->desa_asal);
});

test('invalid nonce ditolak generic', function () {
    $response = $this->get(route('pengajian.hadir', ['nonce' => 'invalid-nonce-12345']));

    $response->assertStatus(200);
    $response->assertSee('tidak valid');
});

test('public route tidak membutuhkan Laravel auth', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);

    $response = $this->get(route('pengajian.hadir', ['nonce' => $result['grant']->nonce]));

    $response->assertStatus(200);
});

test('expired nonce ditolak', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $now = Carbon::now();

    $grant = DesaAccessGrant::create([
        'event_id' => $event->id,
        'desa_id' => $desa->id,
        'token_hash' => 'hash',
        'token_prefix' => 'EXP',
        'valid_from' => $now->copy()->subDay(),
        'valid_until' => $now->copy()->addDay(),
        'nonce' => 'expired-nonce-'.str()->random(16),
        'nonce_expires_at' => $now->copy()->subMinute(),
    ]);

    $response = $this->get(route('pengajian.hadir', ['nonce' => $grant->nonce]));

    $response->assertStatus(200);
    $response->assertSee('tidak valid');
});

test('revoked grant nonce ditolak', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];

    app(DesaAccessService::class)->revokeGrant($grant);

    $response = $this->get(route('pengajian.hadir', ['nonce' => $grant->nonce]));

    $response->assertStatus(200);
    $response->assertSee('tidak valid');
});

test('expired grant ditolak', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $now = Carbon::now();

    $grant = DesaAccessGrant::create([
        'event_id' => $event->id,
        'desa_id' => $desa->id,
        'token_hash' => 'hash',
        'token_prefix' => 'EXG',
        'valid_from' => $now->copy()->subDays(2),
        'valid_until' => $now->copy()->subDay(),
        'nonce' => 'exp-grant-'.str()->random(16),
        'nonce_expires_at' => $now->copy()->addDay(),
    ]);

    $response = $this->get(route('pengajian.hadir', ['nonce' => $grant->nonce]));

    $response->assertStatus(200);
    $response->assertSee('tidak valid');
});

test('not-yet-valid grant ditolak', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $now = Carbon::now();

    $grant = DesaAccessGrant::create([
        'event_id' => $event->id,
        'desa_id' => $desa->id,
        'token_hash' => 'hash',
        'token_prefix' => 'NYV',
        'valid_from' => $now->copy()->addDay(),
        'valid_until' => $now->copy()->addDays(2),
        'nonce' => 'nyv-nonce-'.str()->random(16),
        'nonce_expires_at' => $now->copy()->addDays(2),
    ]);

    $response = $this->get(route('pengajian.hadir', ['nonce' => $grant->nonce]));

    $response->assertStatus(200);
    $response->assertSee('tidak valid');
});

// ===========================================================================
// QR ACCESS — Payload contract
// ===========================================================================

test('QR payload menggunakan nonce URL', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];

    $expectedUrl = route('pengajian.hadir', ['nonce' => $grant->nonce], absolute: false);

    expect($expectedUrl)->toContain('/pengajian/hadir/')
        ->and($expectedUrl)->not->toContain('token')
        ->and($expectedUrl)->toContain($grant->nonce);
});

test('QR payload tidak mengandung raw operator token', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $rawToken = $result['raw_token'];

    $qrUrl = route('pengajian.hadir', ['nonce' => $grant->nonce], absolute: false);

    expect($qrUrl)->not->toContain($rawToken);
    expect($qrUrl)->not->toContain('token');
});

test('QR payload tidak mengandung Person attendance_code', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-SECRET01',
        'jenis_peserta' => 'Pengajian Desa',
    ]);

    $qrUrl = route('pengajian.hadir', ['nonce' => $grant->nonce], absolute: false);

    expect($qrUrl)->not->toContain($participation->attendance_code);
});

test('QR payload tidak mengandung NIP', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];

    $qrUrl = route('pengajian.hadir', ['nonce' => $grant->nonce], absolute: false);

    expect($qrUrl)->not->toContain('nip');
    expect($qrUrl)->not->toContain('12345');
});

// ===========================================================================
// OPERATOR QR PAGE
// ===========================================================================

test('Operator QR page membutuhkan valid scoped session', function () {
    $response = $this->get(route('pengajian.qr-print'));

    $response->assertRedirect(route('pengajian.enter-token'));
});

test('Operator QR page tampil dengan session', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];

    session([
        'pengajian_access' => [
            'grant_id' => $grant->id,
            'event_id' => $event->id,
            'desa_id' => $desa->id,
        ],
    ]);

    $response = $this->get(route('pengajian.qr-print'));

    $response->assertStatus(200);
    $response->assertSee($event->name);
    $response->assertSee($desa->desa_asal);
});

test('Operator dashboard redirects on cross-desa session', function () {
    $event = pgm6_event();
    $desaA = pgm6_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm6_desa(['desa_asal' => 'Desa B']);
    $result = pgm6_grant($event, $desaA);
    $grant = $result['grant'];

    session([
        'pengajian_access' => [
            'grant_id' => $grant->id,
            'event_id' => $event->id,
            'desa_id' => $desaB->id,
        ],
    ]);

    $component = Livewire::test(DesaDashboard::class);

    $component->assertRedirect(route('pengajian.enter-token'));
});

// ===========================================================================
// SEARCH — Desa-scoped via SelfAttendance
// ===========================================================================

test('search dari nonce Desa A hanya menemukan Person Desa A', function () {
    $event = pgm6_event();
    $desaA = pgm6_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm6_desa(['desa_asal' => 'Desa B']);
    $result = pgm6_grant($event, $desaA);
    $grant = $result['grant'];

    pgm6_person('Jono', 'L', $desaA->id);
    pgm6_person('Joni', 'L', $desaA->id);
    pgm6_person('Jono B', 'L', $desaB->id);

    $component = Livewire::test(SelfAttendance::class, ['nonce' => $grant->nonce])
        ->set('query', 'Jono')
        ->call('search');

    $component->assertSet('searchResults', fn ($results) =>
        count($results) === 1 && $results[0]['nama'] === 'Jono'
    );
});

test('Person Desa B tidak muncul', function () {
    $event = pgm6_event();
    $desaA = pgm6_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm6_desa(['desa_asal' => 'Desa B']);
    $result = pgm6_grant($event, $desaA);
    $grant = $result['grant'];

    pgm6_person('Jono Desa B', 'L', $desaB->id);

    $component = Livewire::test(SelfAttendance::class, ['nonce' => $grant->nonce])
        ->set('query', 'Jono')
        ->call('search');

    $component->assertSet('searchResults', []);
});

test('short query search mengikuti PGM.5 contract', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];

    pgm6_person('Jono', 'L', $desa->id);

    $component = Livewire::test(SelfAttendance::class, ['nonce' => $grant->nonce])
        ->set('query', 'Jo')
        ->call('search');

    $component->assertSet('searchResults', []);
});

test('search result limited', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];

    foreach (range(1, 25) as $i) {
        pgm6_person('Orang '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'L', $desa->id);
    }

    $component = Livewire::test(SelfAttendance::class, ['nonce' => $grant->nonce])
        ->set('query', 'Orang')
        ->call('search');

    $component->assertSet('searchResults', fn ($results) => count($results) <= 20);
});

test('search result tidak expose NIP', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];

    $person = pgm6_person('Jono', 'L', $desa->id);
    $person->update(['nip' => 99999]);

    $component = Livewire::test(SelfAttendance::class, ['nonce' => $grant->nonce])
        ->set('query', 'Jono')
        ->call('search');

    $component->assertSet('searchResults', fn ($results) => ! isset($results[0]['nip']));
});

test('search result tidak expose attendance_code', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];

    pgm6_person('Jono', 'L', $desa->id);

    $component = Livewire::test(SelfAttendance::class, ['nonce' => $grant->nonce])
        ->set('query', 'Jono')
        ->call('search');

    $component->assertSet('searchResults', fn ($results) => ! isset($results[0]['attendance_code']));
});

test('search result tidak expose participant_number', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];

    pgm6_person('Jono', 'L', $desa->id);

    $component = Livewire::test(SelfAttendance::class, ['nonce' => $grant->nonce])
        ->set('query', 'Jono')
        ->call('search');

    $component->assertSet('searchResults', fn ($results) => ! isset($results[0]['participant_number']));
});

test('full tanggal_lahir tidak terekspos di search result', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];

    pgm6_person('Jono', 'L', $desa->id, '2000-01-15');

    $component = Livewire::test(SelfAttendance::class, ['nonce' => $grant->nonce])
        ->set('query', 'Jono')
        ->call('search');

    $component->assertSet('searchResults', fn ($results) =>
        ! isset($results[0]['tanggal_lahir'])
            && ! str_contains($results[0]['birth_date_masked'] ?? '', '2000')
    );
});

// ===========================================================================
// VERIFICATION
// ===========================================================================

test('correct birth date verification success', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id, '2000-01-15');

    $verified = app(PengajianIdentityService::class)
        ->verifyBirthDate($person, '2000-01-15');

    expect($verified)->toBeTrue();
});

test('wrong birth date rejected', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id, '2000-01-15');

    $verified = app(PengajianIdentityService::class)
        ->verifyBirthDate($person, '2000-01-16');

    expect($verified)->toBeFalse();
});

test('Person tanpa tanggal_lahir mengikuti fallback contract', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id, null);

    $component = Livewire::test(SelfAttendance::class, ['nonce' => $grant->nonce]);

    $component->set('query', 'Jono')
        ->call('search')
        ->call('selectPerson', $person->id);

    $component->assertSet('step', 4);
    $component->assertSet('selectedPersonId', $person->id);
});

test('selected Person Desa lain ditolak saat submit', function () {
    $event = pgm6_event();
    $desaA = pgm6_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm6_desa(['desa_asal' => 'Desa B']);
    $result = pgm6_grant($event, $desaA);
    $grant = $result['grant'];

    $personB = pgm6_person('Jono', 'L', $desaB->id, '2000-01-15');

    $found = app(PengajianIdentityService::class)
        ->findPersonInDesa($personB->id, $desaA->id);

    expect($found)->toBeNull();
});

// ===========================================================================
// ATTENDANCE
// ===========================================================================

test('valid self-attendance membuat EventAttendance dengan method self', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id, '2000-01-15');

    $attendance = app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant);

    expect($attendance->method)->toBe('self');
    expect($attendance->recorded_by)->toBeNull();
});

test('valid self-attendance membuat Participation', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant);

    expect(Participation::count())->toBe(1);
});

test('Participation memiliki participant_number dan attendance_code', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant);

    $participation = Participation::first();
    expect($participation->participant_number)->toMatch('/^(KL|KP)\d{3}$/');
    expect($participation->attendance_code)->toMatch('/^KJA-/');
});

test('EventAttendance method self dan recorded_by null', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant);

    $attendance = EventAttendance::first();
    expect($attendance->method)->toBe('self');
    expect($attendance->recorded_by)->toBeNull();
});

test('duplicate submit tidak membuat row kedua', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant);

    expect(fn () => app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant))
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(EventAttendance::count())->toBe(1);
});

test('existing Participation direuse', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant);

    expect(fn () => app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant))
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect(Participation::count())->toBe(1);
});

test('existing identifiers tidak berubah', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant);

    $participation = Participation::first();
    $originalNumber = $participation->participant_number;
    $originalCode = $participation->attendance_code;

    expect(fn () => app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant))
        ->toThrow(\Illuminate\Database\QueryException::class);

    expect($participation->fresh()->participant_number)->toBe($originalNumber);
    expect($participation->fresh()->attendance_code)->toBe($originalCode);
});

test('Participation Event lain tidak direuse', function () {
    $event1 = pgm6_event(['name' => 'Event 1']);
    $event2 = pgm6_event(['name' => 'Event 2']);
    $desa = pgm6_desa();
    $result1 = pgm6_grant($event1, $desa);
    $result2 = pgm6_grant($event2, $desa);
    $person = pgm6_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $result1['grant']);

    app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $result2['grant']);

    expect(Participation::count())->toBe(2);
});

test('revoked grant antara search dan submit ditolak', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id, '2000-01-15');

    app(DesaAccessService::class)->revokeGrant($grant);

    expect(fn () => app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant))
        ->toThrow(\RuntimeException::class);
});

test('expired nonce antara search dan submit ditolak', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', $desa->id, '2000-01-15');

    $grant->update(['nonce_expires_at' => Carbon::now()->subMinute()]);

    expect(fn () => app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant))
        ->toThrow(\RuntimeException::class);
});

test('Cross-Desa Person ditolak di service layer', function () {
    $event = pgm6_event();
    $desaA = pgm6_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm6_desa(['desa_asal' => 'Desa B']);
    $result = pgm6_grant($event, $desaA);
    $grant = $result['grant'];
    $personB = pgm6_person('Jono B', 'L', $desaB->id);

    expect(fn () => app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($personB, $grant))
        ->toThrow(\RuntimeException::class, 'tidak terdaftar di desa ini');
});

// ===========================================================================
// CORRECTION
// ===========================================================================

test('Correction request dapat dikirim dari public flow', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $person = pgm6_person('Jono', 'L', $desa->id);

    $request = app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['reason' => 'Nama saya Jono Sukirman'],
        $event->id,
        $desa->id,
    );

    expect($request->status)->toBe('pending');
    expect($request->person_id)->toBe($person->id);
    expect($request->reason)->toBe('Nama saya Jono Sukirman');
});

test('Correction request tidak langsung mengubah Person', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $person = pgm6_person('Jono', 'L', $desa->id);

    app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['requested_name' => 'Jono Baru', 'reason' => 'Test'],
        $event->id,
        $desa->id,
    );

    expect($person->fresh()->nama)->toBe('Jono');
});

test('Correction request scoped ke Person/Event/Desa yang benar', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $person = pgm6_person('Jono', 'L', $desa->id);

    $request = app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['reason' => 'Koreksi data'],
        $event->id,
        $desa->id,
    );

    expect($request->person_id)->toBe($person->id);
    expect($request->event_id)->toBe($event->id);
    expect($request->desa_id)->toBe($desa->id);
});

test('Cross-Desa correction ditolak', function () {
    $desaA = pgm6_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm6_desa(['desa_asal' => 'Desa B']);
    $personA = pgm6_person('Jono A', 'L', $desaA->id);

    expect(fn () => app(PengajianIdentityService::class)->submitCorrection(
        $personA,
        ['reason' => 'Test'],
        null,
        $desaB->id,
    ))->toThrow(\RuntimeException::class);
});

test('Duplicate pending correction mengikuti PGM.5 contract', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $person = pgm6_person('Jono', 'L', $desa->id);

    app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['reason' => 'Pertama'],
        $event->id,
        $desa->id,
    );

    expect(fn () => app(PengajianIdentityService::class)->submitCorrection(
        $person,
        ['reason' => 'Kedua'],
        $event->id,
        $desa->id,
    ))->toThrow(\RuntimeException::class);
});

// ===========================================================================
// Person.desa_id NULL tidak auto-assign dari public QR scope
// ===========================================================================

test('Person Desa NULL tidak diassign dari public QR scope', function () {
    $event = pgm6_event();
    $desa = pgm6_desa();
    $result = pgm6_grant($event, $desa);
    $grant = $result['grant'];
    $person = pgm6_person('Jono', 'L', null);

    expect(fn () => app(PengajianAttendanceService::class)
        ->attendPersonPublicContext($person, $grant))
        ->toThrow(\RuntimeException::class, 'tidak memiliki desa');
});
