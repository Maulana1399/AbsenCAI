<?php

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

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pgm7_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian PGM7',
        'slug' => 'pengajian-pgm7-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pgm7_desa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa PGM7 '.str()->random(4),
    ], $overrides));
}

function pgm7_grant(Event $event, desa $desa): DesaAccessGrant
{
    $now = Carbon::now();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subHour(),
        $now->copy()->addHour(),
    );
    return $result['grant'];
}

function pgm7_person(string $nama, string $gender = 'L', ?int $desaId = null, ?string $birthDate = null): Person
{
    return Person::create([
        'nama' => $nama,
        'jenis_kelamin' => $gender,
        'desa_id' => $desaId,
        'tanggal_lahir' => $birthDate,
    ]);
}

// ---------------------------------------------------------------------------
// 1–3. Search scope
// ---------------------------------------------------------------------------

test('Operator scoped Desa A hanya search Person Desa A', function () {
    $event = pgm7_event();
    $desaA = pgm7_desa(['desa_asal' => 'Desa A']);
    $grant = pgm7_grant($event, $desaA);
    pgm7_person('Jono', 'L', $desaA->id);
    pgm7_person('Joni', 'L', $desaA->id);

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Jono');

    expect($results)->toHaveCount(1)
        ->and($results[0]['nama'])->toBe('Jono');
});

test('Person Desa B tidak muncul pada search Desa A', function () {
    $event = pgm7_event();
    $desaA = pgm7_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm7_desa(['desa_asal' => 'Desa B']);
    $grant = pgm7_grant($event, $desaA);
    pgm7_person('Jono Desa A', 'L', $desaA->id);
    pgm7_person('Jono Desa B', 'L', $desaB->id);

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Jono');

    expect($results)->toHaveCount(1)
        ->and($results[0]['nama'])->toBe('Jono Desa A');
});

test('Person desa_id NULL tidak muncul pada search', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    pgm7_person('Jono', 'L', $desa->id);
    pgm7_person('Null Desa', 'L', null);

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Null');

    expect($results)->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// 4–6. Valid operator attendance
// ---------------------------------------------------------------------------

test('Valid operator attendance creates Participation', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    expect(Participation::count())->toBe(1);
});

test('EventAttendance method = operator', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    $attendance = EventAttendance::first();
    expect($attendance->method)->toBe('operator');
});

test('recorded_by null jika operator token-only', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    $attendance = EventAttendance::first();
    expect($attendance->recorded_by)->toBeNull();
});

// ---------------------------------------------------------------------------
// 8–10. Existing Participation direuse
// ---------------------------------------------------------------------------

test('Existing Participation direuse oleh operator', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class, 'sudah tercatat hadir');

    expect(Participation::count())->toBe(1);
});

test('participant_number tidak berubah oleh operator', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    $originalNumber = Participation::first()->participant_number;

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class);

    expect(Participation::first()->fresh()->participant_number)->toBe($originalNumber);
});

test('attendance_code tidak berubah oleh operator', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    $originalCode = Participation::first()->attendance_code;

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class);

    expect(Participation::first()->fresh()->attendance_code)->toBe($originalCode);
});

// ---------------------------------------------------------------------------
// 11–13. Duplicate handling
// ---------------------------------------------------------------------------

test('Duplicate operator submit tidak create row kedua', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class);

    expect(EventAttendance::count())->toBe(1);
});

test('Existing self attendance tidak dioverwrite oleh operator', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonPublicContext(
        $person, $grant,
    );

    $attendance = EventAttendance::first();
    expect($attendance->method)->toBe('self');

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class, 'sudah tercatat hadir');

    expect(EventAttendance::count())->toBe(1);
    expect($attendance->fresh()->method)->toBe('self');
});

test('Existing operator attendance tidak dioverwrite oleh self', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    $attendance = EventAttendance::first();
    expect($attendance->method)->toBe('operator');

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonPublicContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class, 'Peserta sudah tercatat hadir.');

    expect(EventAttendance::count())->toBe(1);
    expect($attendance->fresh()->method)->toBe('operator');
});

// ---------------------------------------------------------------------------
// 14–17. Rejection
// ---------------------------------------------------------------------------

test('Cross-Desa Person ditolak di operator context', function () {
    $event = pgm7_event();
    $desaA = pgm7_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm7_desa(['desa_asal' => 'Desa B']);
    $grant = pgm7_grant($event, $desaA);
    $personB = pgm7_person('Jono', 'L', $desaB->id);

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $personB, $grant,
    ))->toThrow(\RuntimeException::class, 'tidak terdaftar di desa ini');
});

test('Revoked grant ditolak saat submit operator', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id);

    app(DesaAccessService::class)->revokeGrant($grant);

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class, 'tidak valid');
});

test('Expired grant ditolak saat submit operator', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $now = Carbon::now();
    $expiredGrant = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subHours(3),
        $now->copy()->subHour(),
    );
    $grant = $expiredGrant['grant'];
    $person = pgm7_person('Jono', 'L', $desa->id);

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class, 'tidak valid');
});

test('Person desa_id NULL ditolak di operator context', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', null);

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class, 'tidak memiliki desa');
});

// ---------------------------------------------------------------------------
// 18. Event isolation
// ---------------------------------------------------------------------------

test('Event isolation terjaga di operator context', function () {
    $event1 = pgm7_event(['name' => 'Event 1']);
    $event2 = pgm7_event(['name' => 'Event 2']);
    $desa = pgm7_desa();
    $grant1 = pgm7_grant($event1, $desa);
    $grant2 = pgm7_grant($event2, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant1,
    );

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant2,
    );

    expect(Participation::count())->toBe(2);
    expect(EventAttendance::count())->toBe(2);
});

// ---------------------------------------------------------------------------
// 38–44. UI/Access
// ---------------------------------------------------------------------------

test('Dashboard membutuhkan valid Pengajian scoped session', function () {
    $response = $this->get(route('pengajian.desa', absolute: false));

    $response->assertRedirect(route('pengajian.enter-token', absolute: false));
});

test('Invalid session ditolak dashboard', function () {
    session()->put('pengajian_access', [
        'grant_id' => 99999,
        'event_id' => 1,
        'desa_id' => 1,
    ]);

    $response = $this->get(route('pengajian.desa', absolute: false));

    $response->assertRedirect(route('pengajian.enter-token', absolute: false));
});

test('Cross-Desa session manipulation ditolak', function () {
    $event = pgm7_event();
    $desaA = pgm7_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm7_desa(['desa_asal' => 'Desa B']);
    $grant = pgm7_grant($event, $desaA);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $event->id,
        'desa_id' => $desaB->id,
    ]);

    $response = $this->get(route('pengajian.desa', absolute: false));

    $response->assertRedirect(route('pengajian.enter-token', absolute: false));
});

test('Dashboard summary scoped Event + Desa', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);

    session()->put('pengajian_access', [
        'grant_id' => $grant->id,
        'event_id' => $event->id,
        'desa_id' => $desa->id,
    ]);

    $response = $this->get(route('pengajian.desa', absolute: false));

    $response->assertStatus(200);
    $response->assertSee($event->name);
    $response->assertSee($desa->desa_asal);
});

test('Operator attendance action revalidates grant', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id);

    app(DesaAccessService::class)->revokeGrant($grant);

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class, 'tidak valid');
});

test('QR PGM.6 tetap accessible dari dashboard', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);

    $nonce = $grant->nonce;

    $response = $this->get(route('pengajian.hadir', ['nonce' => $nonce], absolute: false));

    $response->assertStatus(200);
});

test('Public self-attendance PGM.6 tetap bekerja', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Jono', 'L', $desa->id, '2000-01-15');

    $attendance = app(PengajianAttendanceService::class)->attendPersonPublicContext(
        $person, $grant,
    );

    expect($attendance->method)->toBe('self');
    expect($attendance->recorded_by)->toBeNull();
});

// ===========================================================================
// PGM.15 — Operator search with attended_at from raw join
// ===========================================================================

test('operator search attended participant returns status Hadir tanpa exception', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    $person = pgm7_person('Rasen Hadir', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    $results = app(PengajianIdentityService::class)
        ->searchPersonsForOperator($grant, 'Rasen');

    expect($results)->toHaveCount(1);
    expect($results[0]['nama'])->toBe('Rasen Hadir');
    expect($results[0]['hadir'])->toBeTrue();
});

test('operator search non-attended participant returns null attended_at', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);
    pgm7_person('Belum Absen', 'L', $desa->id);

    $results = app(PengajianIdentityService::class)
        ->searchPersonsForOperator($grant, 'Belum');

    expect($results)->toHaveCount(1);
    expect($results[0]['hadir'])->toBeFalse();
    expect($results[0]['attended_at'])->toBeNull();
    expect($results[0]['method'])->toBeNull();
});

test('operator search with mixed attended and unattended returns correct statuses', function () {
    $event = pgm7_event();
    $desa = pgm7_desa();
    $grant = pgm7_grant($event, $desa);

    $hadir = pgm7_person('Siti Hadir', 'P', $desa->id);
    pgm7_person('Siti Belum', 'P', $desa->id);
    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $hadir, $grant,
    );

    $results = app(PengajianIdentityService::class)
        ->searchPersonsForOperator($grant, 'Siti');

    expect($results)->toHaveCount(2);
    expect(collect($results)->firstWhere('hadir', true)['nama'])->toBe('Siti Hadir');
    expect(collect($results)->firstWhere('hadir', false)['nama'])->toBe('Siti Belum');
});
