<?php

use App\Enums\Role;
use App\Models\desa;
use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Person;
use App\Models\User;
use App\Services\Pengajian\DesaAccessService;
use App\Services\Pengajian\IdentityCorrectionService;
use App\Services\Pengajian\PengajianAttendanceService;
use App\Services\Pengajian\PengajianIdentityService;
use App\Support\ActiveEventContext;
use Carbon\Carbon;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pgm9s_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian Security',
        'slug' => 'pengajian-security-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pgm9s_desa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Security '.str()->random(4),
    ], $overrides));
}

function pgm9s_person(string $nama, string $gender = 'L', ?int $desaId = null, ?string $birthDate = null): Person
{
    return Person::create([
        'nama' => $nama,
        'jenis_kelamin' => $gender,
        'desa_id' => $desaId,
        'tanggal_lahir' => $birthDate,
    ]);
}

function pgm9s_grant(Event $event, desa $desa, ?int $nonceTtlMinutes = 1440): DesaAccessGrant
{
    $now = Carbon::now();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subHour(),
        $now->copy()->addHour(),
        nonceTtlMinutes: $nonceTtlMinutes,
    );

    return $result['grant'];
}

// ---------------------------------------------------------------------------
// 1-5: Authentication & Authorization
// ---------------------------------------------------------------------------

test('1. Guest cannot access event index', function () {
    $this->get(route('events.index'))->assertRedirect(route('login'));
});

test('2. Guest cannot access identity correction review', function () {
    $this->get(route('koreksi.data'))->assertRedirect(route('login'));
});

test('3. Guest cannot access regional report', function () {
    $event = pgm9s_event();
    $this->get(route('pengajian.report', ['event' => $event]))->assertRedirect(route('login'));
});

test('4. Authenticated unverified user can access auth-only routes (verified is not an authorization boundary)', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/settings/profile')
        ->assertOk();

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertForbidden();
});

test('5. Verified user can access admin routes', function () {
    $event = pgm9s_event();
    $user = User::factory()->create(['role' => Role::Admin]);
    app(ActiveEventContext::class)->set($event);

    $this->actingAs($user)
        ->get(route('events.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('koreksi.data'))
        ->assertOk();
});

// ---------------------------------------------------------------------------
// 6-10: Grant & Session Security
// ---------------------------------------------------------------------------

test('6. Token not valid after revocation', function () {
    $event = pgm9s_event();
    $desa = pgm9s_desa();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        Carbon::now()->subHour(), Carbon::now()->addHour(),
    );

    app(DesaAccessService::class)->revokeGrant($result['grant']);

    $found = app(DesaAccessService::class)->findGrantByToken($result['raw_token']);

    expect($found)->toBeNull();
});

test('7. Token not valid before valid_from', function () {
    $event = pgm9s_event();
    $desa = pgm9s_desa();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        Carbon::now()->addHour(), Carbon::now()->addHours(2),
    );

    $found = app(DesaAccessService::class)->findGrantByToken($result['raw_token']);

    expect($found)->toBeNull();
});

test('8. Token not valid after valid_until', function () {
    $event = pgm9s_event();
    $desa = pgm9s_desa();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        Carbon::now()->subHours(2), Carbon::now()->subHour(),
    );

    $found = app(DesaAccessService::class)->findGrantByToken($result['raw_token']);

    expect($found)->toBeNull();
});

test('9. Nonce rotated after rotation', function () {
    $event = pgm9s_event();
    $desa = pgm9s_desa();
    $grant = pgm9s_grant($event, $desa);

    $oldNonce = $grant->nonce;

    app(DesaAccessService::class)->rotateNonce($grant);

    $resolved = app(DesaAccessService::class)->resolveNonce($oldNonce);

    expect($resolved)->toBeNull();
});

test('10. Nonce expires after nonce_expires_at', function () {
    $event = pgm9s_event();
    $desa = pgm9s_desa();
    $grant = pgm9s_grant($event, $desa, nonceTtlMinutes: 0);

    $this->travel(1)->minute();

    expect($grant->fresh()->isNonceValid())->toBeFalse();
});

// ---------------------------------------------------------------------------
// 11-15: Cross-boundary & Scope Enforcement
// ---------------------------------------------------------------------------

test('11. Grant for Event A cannot access Event B', function () {
    $eventA = pgm9s_event(['name' => 'Event A']);
    $eventB = pgm9s_event(['name' => 'Event B']);
    $desa = pgm9s_desa();
    $grantA = pgm9s_grant($eventA, $desa);
    $result = app(DesaAccessService::class)->createGrant(
        $eventA, $desa,
        Carbon::now()->subHour(), Carbon::now()->addHour(),
    );

    $found = app(DesaAccessService::class)->validateToken($result['raw_token'], $eventB->id);

    expect($found)->toBeNull();
});

test('12. Identity search scoped to desa', function () {
    $event = pgm9s_event();
    $desaA = pgm9s_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm9s_desa(['desa_asal' => 'Desa B']);
    $grant = pgm9s_grant($event, $desaA);

    pgm9s_person('Spesifik Desa A', 'L', $desaA->id);
    pgm9s_person('Spesifik Desa B', 'L', $desaB->id);

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Spesifik');

    expect($results)->toHaveCount(1)
        ->and($results[0]['nama'])->toBe('Spesifik Desa A');
});

test('13. Attendance scoped to event and desa', function () {
    $event1 = pgm9s_event(['name' => 'Event 1']);
    $event2 = pgm9s_event(['name' => 'Event 2']);
    $desa = pgm9s_desa();
    $grant1 = pgm9s_grant($event1, $desa);

    $person = pgm9s_person('Jono', 'L', $desa->id);
    app(PengajianAttendanceService::class)->attendPersonOperatorContext($person, $grant1);

    $attendanceForEvent1 = EventAttendance::query()
        ->whereExists(function ($q) use ($event1, $person) {
            $q->selectRaw('1')
                ->from('participations')
                ->whereColumn('participations.id', 'event_attendances.participation_id')
                ->where('participations.event_id', $event1->id)
                ->where('participations.person_id', $person->id);
        })
        ->count();

    $attendanceForEvent2 = EventAttendance::query()
        ->whereExists(function ($q) use ($event2, $person) {
            $q->selectRaw('1')
                ->from('participations')
                ->whereColumn('participations.id', 'event_attendances.participation_id')
                ->where('participations.event_id', $event2->id)
                ->where('participations.person_id', $person->id);
        })
        ->count();

    expect($attendanceForEvent1)->toBe(1);
    expect($attendanceForEvent2)->toBe(0);
});

test('14. Duplicate attendance prevented by UNIQUE constraint', function () {
    $event = pgm9s_event();
    $desa = pgm9s_desa();
    $grant = pgm9s_grant($event, $desa);

    $person = pgm9s_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext($person, $grant);

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant
    ))->toThrow(\RuntimeException::class);

    expect(EventAttendance::count())->toBe(1);
});

test('15. Person without desa_id cannot be attended', function () {
    $event = pgm9s_event();
    $desa = pgm9s_desa();
    $grant = pgm9s_grant($event, $desa);

    $person = pgm9s_person('Jono', 'L', null);

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant
    ))->toThrow(\RuntimeException::class, 'Person tidak memiliki desa assignment.');
});

// ---------------------------------------------------------------------------
// 16-20: Data Exposure & Identity Security
// ---------------------------------------------------------------------------

test('16. Person from different desa cannot be attended', function () {
    $event = pgm9s_event();
    $desaA = pgm9s_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm9s_desa(['desa_asal' => 'Desa B']);
    $grant = pgm9s_grant($event, $desaA);

    $person = pgm9s_person('Jono', 'L', $desaB->id);

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant
    ))->toThrow(\RuntimeException::class, 'Person tidak terdaftar di desa ini.');
});

test('17. findPersonInDesa returns null for wrong desa', function () {
    $event = pgm9s_event();
    $desaA = pgm9s_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm9s_desa(['desa_asal' => 'Desa B']);

    $person = pgm9s_person('Jono', 'L', $desaB->id);

    $found = app(PengajianIdentityService::class)->findPersonInDesa($person->id, $desaA->id);

    expect($found)->toBeNull();
});

test('18. Correction submit from public requires valid nonce', function () {
    $event = pgm9s_event();
    $desa = pgm9s_desa();
    $grant = pgm9s_grant($event, $desa);

    $person = pgm9s_person('Jono', 'L', $desa->id, '2000-01-15');

    app(DesaAccessService::class)->revokeGrant($grant);

    expect(fn () => app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant->fresh(), ['requested_name' => 'Jono Baru']
    ))->toThrow(\RuntimeException::class, 'Sesi QR tidak valid atau sudah kedaluwarsa.');
});

test('19. Guest cannot access identity correction review page', function () {
    $event = pgm9s_event();
    $desa = pgm9s_desa();
    $grant = pgm9s_grant($event, $desa);
    $person = pgm9s_person('Jono', 'L', $desa->id, '2000-01-15');

    app(IdentityCorrectionService::class)->submitFromPublicContext(
        $person, $grant,
        ['requested_name' => 'Jono Baru'],
    );

    $this->get(route('koreksi.data'))->assertRedirect(route('login'));
});

test('20. NIP and attendance_code not exposed by IdentitySearchService', function () {
    $event = pgm9s_event();
    $desa = pgm9s_desa();
    $grant = pgm9s_grant($event, $desa);

    pgm9s_person('Jono', 'L', $desa->id, '2000-01-15');

    $results = app(PengajianIdentityService::class)->searchPersons($grant, 'Jono');

    expect($results[0])->not->toHaveKey('nip');
    expect($results[0])->not->toHaveKey('attendance_code');
});
