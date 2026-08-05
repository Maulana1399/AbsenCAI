<?php

use App\Enums\Role;
use App\Models\desa;
use App\Models\Event;
use App\Models\Person;
use App\Models\User;
use App\Services\Pengajian\DesaAccessService;
use App\Services\Pengajian\PengajianAttendanceService;
use App\Services\Pengajian\PengajianRegionalReportService;
use App\Support\ActiveEventContext;
use Carbon\Carbon;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pgm9_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian Regional',
        'slug' => 'pengajian-regional-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pgm9_desa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Regional '.str()->random(4),
    ], $overrides));
}

function pgm9_person(string $nama, string $gender = 'L', ?int $desaId = null, ?string $birthDate = null): Person
{
    return Person::create([
        'nama' => $nama,
        'jenis_kelamin' => $gender,
        'desa_id' => $desaId,
        'tanggal_lahir' => $birthDate,
    ]);
}

function pgm9_grant(Event $event, desa $desa): \App\Models\DesaAccessGrant
{
    $now = Carbon::now();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subHour(),
        $now->copy()->addHour(),
    );

    return $result['grant'];
}

function pgm9_attend(Person $person, \App\Models\DesaAccessGrant $grant, string $method = 'operator'): void
{
    if ($method === 'self') {
        app(PengajianAttendanceService::class)->attendPersonPublicContext($person, $grant);
    } else {
        app(PengajianAttendanceService::class)->attendPersonOperatorContext($person, $grant);
    }
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

test('summary mencakup semua desa', function () {
    $event = pgm9_event();
    pgm9_person('Jono A', 'L', pgm9_desa()->id);
    pgm9_person('Joni B', 'L', pgm9_desa()->id);

    $summary = app(PengajianRegionalReportService::class)->summary($event);

    expect($summary['total_warga'])->toBe(2);
});

test('summary mengabaikan Person tanpa desa_id', function () {
    $event = pgm9_event();
    pgm9_person('Jono', 'L', pgm9_desa()->id);
    pgm9_person('Joni NoDesa', 'L', null);

    $summary = app(PengajianRegionalReportService::class)->summary($event);

    expect($summary['total_warga'])->toBe(1);
});

test('summary sudah_hadir dihitung per event', function () {
    $event = pgm9_event();
    $desa = pgm9_desa();
    $grant = pgm9_grant($event, $desa);

    $hadir1 = pgm9_person('Hadir', 'L', $desa->id);
    pgm9_person('Belum', 'L', $desa->id);
    $hadir2 = pgm9_person('Juga Hadir', 'L', $desa->id);
    pgm9_attend($hadir1, $grant);
    pgm9_attend($hadir2, $grant);

    $summary = app(PengajianRegionalReportService::class)->summary($event);

    expect($summary['sudah_hadir'])->toBe(2);
});

test('summary menghitung belum_hadir dengan benar', function () {
    $event = pgm9_event();
    $desa = pgm9_desa();
    $grant = pgm9_grant($event, $desa);

    $hadir1 = pgm9_person('Hadir', 'L', $desa->id);
    pgm9_person('Belum', 'L', $desa->id);
    $hadir2 = pgm9_person('Hadir Juga', 'L', $desa->id);
    pgm9_attend($hadir1, $grant);
    pgm9_attend($hadir2, $grant);

    $summary = app(PengajianRegionalReportService::class)->summary($event);

    expect($summary['total_warga'])->toBe(3);
    expect($summary['sudah_hadir'])->toBe(2);
    expect($summary['belum_hadir'])->toBe(1);
});

test('summary menghitung self dan operator', function () {
    $event = pgm9_event();
    $desa = pgm9_desa();
    $grant = pgm9_grant($event, $desa);

    $selfPerson = pgm9_person('Self', 'L', $desa->id, '2000-01-15');
    $opPerson = pgm9_person('Op', 'L', $desa->id);

    pgm9_attend($selfPerson, $grant, 'self');
    pgm9_attend($opPerson, $grant, 'operator');

    $summary = app(PengajianRegionalReportService::class)->summary($event);

    expect($summary['self'])->toBe(1);
    expect($summary['operator'])->toBe(1);
});

test('summary menghitung total_desa dan desa_hadir', function () {
    $event = pgm9_event();
    $desaA = pgm9_desa();
    $desaB = pgm9_desa();
    $grantA = pgm9_grant($event, $desaA);

    pgm9_person('Jono A', 'L', $desaA->id);
    pgm9_person('Jono B', 'L', $desaB->id);

    pgm9_attend(pgm9_person('Hadir A', 'L', $desaA->id), $grantA);

    $summary = app(PengajianRegionalReportService::class)->summary($event);

    expect($summary['total_desa'])->toBe(2);
    expect($summary['desa_hadir'])->toBe(1);
});

test('summary event lain tidak memengaruhi', function () {
    $event1 = pgm9_event(['name' => 'Event 1']);
    $event2 = pgm9_event(['name' => 'Event 2']);
    $desa = pgm9_desa();
    $grant1 = pgm9_grant($event1, $desa);

    pgm9_person('Jono', 'L', $desa->id);
    pgm9_attend(pgm9_person('Hadir', 'L', $desa->id), $grant1);

    $summary2 = app(PengajianRegionalReportService::class)->summary($event2);

    expect($summary2['sudah_hadir'])->toBe(0);
});

// ---------------------------------------------------------------------------
// Desa Breakdown
// ---------------------------------------------------------------------------

test('desaBreakdown mengembalikan semua desa', function () {
    $event = pgm9_event();
    $desaA = pgm9_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm9_desa(['desa_asal' => 'Desa B']);

    pgm9_person('Jono A', 'L', $desaA->id);
    pgm9_person('Jono B', 'L', $desaB->id);

    $breakdown = app(PengajianRegionalReportService::class)->desaBreakdown($event);

    expect($breakdown)->toHaveCount(2);
    expect($breakdown[0]['desa_name'])->toBe('Desa A');
    expect($breakdown[1]['desa_name'])->toBe('Desa B');
});

test('desaBreakdown berisi total dan hadir per desa', function () {
    $event = pgm9_event();
    $desa = pgm9_desa();
    $grant = pgm9_grant($event, $desa);

    pgm9_person('Jono', 'L', $desa->id);
    pgm9_attend(pgm9_person('Hadir', 'L', $desa->id), $grant);

    $breakdown = app(PengajianRegionalReportService::class)->desaBreakdown($event);

    expect($breakdown)->toHaveCount(1);
    expect($breakdown[0]['total_warga'])->toBe(2);
    expect($breakdown[0]['sudah_hadir'])->toBe(1);
    expect($breakdown[0]['belum_hadir'])->toBe(1);
});

// ---------------------------------------------------------------------------
// Attendance List
// ---------------------------------------------------------------------------

test('attendanceList tanpa filter desa mencakup semua', function () {
    $event = pgm9_event();
    $desaA = pgm9_desa();
    $desaB = pgm9_desa();

    pgm9_person('Jono A', 'L', $desaA->id);
    pgm9_person('Joni B', 'L', $desaB->id);

    $list = app(PengajianRegionalReportService::class)->attendanceList($event);

    expect($list)->toHaveCount(2);
});

test('attendanceList filter desaId bekerja', function () {
    $event = pgm9_event();
    $desaA = pgm9_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm9_desa(['desa_asal' => 'Desa B']);

    pgm9_person('Jono A', 'L', $desaA->id);
    pgm9_person('Joni B', 'L', $desaB->id);

    $list = app(PengajianRegionalReportService::class)->attendanceList($event, desaId: $desaA->id);

    expect($list)->toHaveCount(1)
        ->and($list[0]['nama'])->toBe('Jono A');
});

test('attendanceList Person tanpa desa_id tidak muncul', function () {
    $event = pgm9_event();
    pgm9_person('Jono', 'L', pgm9_desa()->id);
    pgm9_person('Joni NoDesa', 'L', null);

    $list = app(PengajianRegionalReportService::class)->attendanceList($event);

    expect($list)->toHaveCount(1);
});

test('attendanceList NIP tidak terekspos', function () {
    $event = pgm9_event();
    $desa = pgm9_desa();
    pgm9_person('Jono', 'L', $desa->id);

    $list = app(PengajianRegionalReportService::class)->attendanceList($event);

    expect($list[0])->not->toHaveKey('nip');
});

test('attendanceList attendance_code tidak terekspos', function () {
    $event = pgm9_event();
    $desa = pgm9_desa();
    pgm9_person('Jono', 'L', $desa->id);

    $list = app(PengajianRegionalReportService::class)->attendanceList($event);

    expect($list[0])->not->toHaveKey('attendance_code');
});

test('halaman report membutuhkan auth', function () {
    $event = pgm9_event();
    $response = $this->get(route('pengajian.report', ['event' => $event]));

    $response->assertRedirect(route('login'));
});

test('halaman report dapat diakses oleh user terverifikasi', function () {
    $event = pgm9_event();

    $user = User::factory()->create(['role' => Role::Admin]);
    app(ActiveEventContext::class)->set($event);

    $response = $this->actingAs($user)
        ->get(route('pengajian.report', ['event' => $event]));

    $response->assertOk();
});

test('halaman report tidak 500 ketika tidak ada active event', function () {
    $event = pgm9_event();
    $user = User::factory()->create(['role' => Role::Admin]);

    $response = $this->actingAs($user)
        ->get(route('pengajian.report', ['event' => $event]));

    $response->assertOk();
    $response->assertSee('Total Warga');
});

test('halaman report dengan active event tetap berfungsi normal', function () {
    $event = pgm9_event();
    $desa = pgm9_desa();
    pgm9_person('Jono', 'L', $desa->id);

    $user = User::factory()->create(['role' => Role::Admin]);
    app(ActiveEventContext::class)->set($event);

    $response = $this->actingAs($user)
        ->get(route('pengajian.report', ['event' => $event]));

    $response->assertOk();
    $response->assertSee('Total Warga');
});

// ===========================================================================
// PGM.15 — Regional attendanceList attended_at from raw join
// ===========================================================================

test('regional attendanceList attended_at diformat dengan benar dari raw join', function () {
    $event = pgm9_event();
    $desa = pgm9_desa();
    $person = pgm9_person('Jono', 'L', $desa->id);

    $now = Carbon::now();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subHour(),
        $now->copy()->addHour(),
    );
    $grant = $result['grant'];

    app(PengajianAttendanceService::class)->attendPersonPublicContext(
        $person, $grant,
    );

    $list = app(PengajianRegionalReportService::class)->attendanceList($event);

    expect($list)->toHaveCount(1);
    expect($list[0]['hadir'])->toBeTrue();
    expect($list[0]['attended_at'])->toMatch('/^\d{2} [A-Za-z]{3} \d{4} \d{2}:\d{2}$/');
});

test('regional attendanceList untuk peserta belum hadir memberikan null attended_at', function () {
    $event = pgm9_event();
    $desa = pgm9_desa();
    pgm9_person('Jono', 'L', $desa->id);

    $list = app(PengajianRegionalReportService::class)->attendanceList($event);

    expect($list)->toHaveCount(1);
    expect($list[0]['hadir'])->toBeFalse();
    expect($list[0]['attended_at'])->toBeNull();
});

// ===========================================================================
// PGM.16 — Filter Combination Tests
// ===========================================================================

function pgm16_setup(): array
{
    $event = pgm9_event();
    $desa = pgm9_desa();
    $grant = pgm9_grant($event, $desa);

    $selfPerson = pgm9_person('Self Person', 'L', $desa->id, '2000-01-15');
    $opPerson = pgm9_person('Operator Person', 'L', $desa->id, '1990-06-20');
    $belumPerson = pgm9_person('Belum Person', 'P', $desa->id, '1985-03-10');

    pgm9_attend($selfPerson, $grant, 'self');
    pgm9_attend($opPerson, $grant, 'operator');

    return compact('event', 'desa', 'grant', 'selfPerson', 'opPerson', 'belumPerson');
}

test('filter combination — Semua Status + Semua Metode shows all', function () {
    $d = pgm16_setup();
    $service = app(PengajianRegionalReportService::class);
    $list = $service->attendanceList($d['event']);

    expect($list)->toHaveCount(3);
});

test('filter combination — Hadir + Semua Metode shows all attended', function () {
    $d = pgm16_setup();
    $service = app(PengajianRegionalReportService::class);
    $list = $service->attendanceList($d['event'], status: 'hadir');

    expect($list)->toHaveCount(2)
        ->and($list[0]['hadir'])->toBeTrue()
        ->and($list[1]['hadir'])->toBeTrue();
});

test('filter combination — Hadir + Self shows only self attendance', function () {
    $d = pgm16_setup();
    $service = app(PengajianRegionalReportService::class);
    $list = $service->attendanceList($d['event'], status: 'hadir', method: 'self');

    expect($list)->toHaveCount(1)
        ->and($list[0]['nama'])->toBe('Self Person');
});

test('filter combination — Hadir + Operator shows only operator attendance', function () {
    $d = pgm16_setup();
    $service = app(PengajianRegionalReportService::class);
    $list = $service->attendanceList($d['event'], status: 'hadir', method: 'operator');

    expect($list)->toHaveCount(1)
        ->and($list[0]['nama'])->toBe('Operator Person');
});

test('filter combination — Belum Hadir ignores method filter', function () {
    $d = pgm16_setup();
    $service = app(PengajianRegionalReportService::class);
    $list = $service->attendanceList($d['event'], status: 'belum', method: 'self');

    expect($list)->toHaveCount(1)
        ->and($list[0]['nama'])->toBe('Belum Person')
        ->and($list[0]['hadir'])->toBeFalse();
});

test('filter combination — Search combines with status Hadir', function () {
    $d = pgm16_setup();
    $service = app(PengajianRegionalReportService::class);
    $list = $service->attendanceList($d['event'], search: 'Self', status: 'hadir');

    expect($list)->toHaveCount(1)
        ->and($list[0]['nama'])->toBe('Self Person');
});

test('filter combination — Search combines with status Belum', function () {
    $d = pgm16_setup();
    $service = app(PengajianRegionalReportService::class);
    $list = $service->attendanceList($d['event'], search: 'Belum', status: 'belum');

    expect($list)->toHaveCount(1)
        ->and($list[0]['nama'])->toBe('Belum Person');
});

test('filter combination — Search with Hadir + Method Self', function () {
    $d = pgm16_setup();
    $service = app(PengajianRegionalReportService::class);
    $list = $service->attendanceList($d['event'], search: 'Self', status: 'hadir', method: 'self');

    expect($list)->toHaveCount(1)
        ->and($list[0]['nama'])->toBe('Self Person');
});

test('filter combination — Method-only filter (no status) shows only attended with that method', function () {
    $d = pgm16_setup();
    $service = app(PengajianRegionalReportService::class);
    $list = $service->attendanceList($d['event'], method: 'self');

    expect($list)->toHaveCount(1)
        ->and($list[0]['nama'])->toBe('Self Person')
        ->and($list[0]['hadir'])->toBeTrue();
});

test('filter combination — Empty search returns all when ≥ 3 chars', function () {
    $d = pgm16_setup();
    $service = app(PengajianRegionalReportService::class);
    $list = $service->attendanceList($d['event'], search: 'xy'); // less than 3 chars

    expect($list)->toHaveCount(3);
});

test('filter combination — No stale state between filter changes', function () {
    $d = pgm16_setup();
    $service = app(PengajianRegionalReportService::class);

    // Apply Hadir + Self
    $list1 = $service->attendanceList($d['event'], status: 'hadir', method: 'self');
    expect($list1)->toHaveCount(1)
        ->and($list1[0]['method'])->toBe('self');

    // Change to Hadir + Operator — should not carry over self filter
    $list2 = $service->attendanceList($d['event'], status: 'hadir', method: 'operator');
    expect($list2)->toHaveCount(1)
        ->and($list2[0]['method'])->toBe('operator');

    // Change to Semua Status + Semua Method — should see all 3
    $list3 = $service->attendanceList($d['event']);
    expect($list3)->toHaveCount(3);
});

// ---------------------------------------------------------------------------
// PGM.17 — Bug #9 Livewire Component Filter Integration
// ---------------------------------------------------------------------------

test('Livewire filterStatus update clears method when Belum Hadir', function () {
    $d = pgm16_setup();
    $user = User::factory()->create();
    app(ActiveEventContext::class)->set($d['event']);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Pengajian\RegionalReport::class)
        ->set('activeTab', 'list')
        ->set('filterStatus', 'belum')
        ->assertSet('filterMethod', null);
});

test('Livewire filter Hadir + Self combination', function () {
    $d = pgm16_setup();
    $user = User::factory()->create();
    app(ActiveEventContext::class)->set($d['event']);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Pengajian\RegionalReport::class)
        ->set('activeTab', 'list')
        ->set('filterStatus', 'hadir')
        ->set('filterMethod', 'self')
        ->assertSee('Self Person')
        ->assertDontSee('Operator Person')
        ->assertDontSee('Belum Person');
});

test('Livewire filter Hadir + Operator combination', function () {
    $d = pgm16_setup();
    $user = User::factory()->create();
    app(ActiveEventContext::class)->set($d['event']);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Pengajian\RegionalReport::class)
        ->set('activeTab', 'list')
        ->set('filterStatus', 'hadir')
        ->set('filterMethod', 'operator')
        ->assertSee('Operator Person')
        ->assertDontSee('Self Person')
        ->assertDontSee('Belum Person');
});

test('Livewire filter Belum Hadir shows only unattended', function () {
    $d = pgm16_setup();
    $user = User::factory()->create();
    app(ActiveEventContext::class)->set($d['event']);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Pengajian\RegionalReport::class)
        ->set('activeTab', 'list')
        ->set('filterStatus', 'belum')
        ->assertSee('Belum Person')
        ->assertDontSee('Self Person')
        ->assertDontSee('Operator Person');
});

test('Livewire filter Search + Status + Method combination', function () {
    $d = pgm16_setup();
    $user = User::factory()->create();
    app(ActiveEventContext::class)->set($d['event']);

    // Combined: search "Self" + status Hadir + method Self
    // Note: search minimum length is 3 chars ("Self" == 4 chars, OK)
    Livewire::actingAs($user)
        ->test(\App\Livewire\Pengajian\RegionalReport::class)
        ->set('activeTab', 'list')
        ->set('listSearch', 'Self')
        ->set('filterStatus', 'hadir')
        ->set('filterMethod', 'self')
        ->assertSee('Self Person')
        ->assertDontSee('Operator Person')
        ->assertDontSee('Belum Person');
});

test('Livewire filter changes without manual refresh', function () {
    $d = pgm16_setup();
    $user = User::factory()->create();
    app(ActiveEventContext::class)->set($d['event']);

    $component = Livewire::actingAs($user)
        ->test(\App\Livewire\Pengajian\RegionalReport::class);

    // Initially shows all
    $component->set('activeTab', 'list')
        ->assertSee('Self Person')
        ->assertSee('Operator Person')
        ->assertSee('Belum Person');

    // Apply Hadir filter -> immediately changes (no refresh needed)
    $component->set('filterStatus', 'hadir')
        ->assertSee('Self Person')
        ->assertSee('Operator Person')
        ->assertDontSee('Belum Person');

    // Add Self method -> further narrows
    $component->set('filterMethod', 'self')
        ->assertSee('Self Person')
        ->assertDontSee('Operator Person')
        ->assertDontSee('Belum Person');
});
