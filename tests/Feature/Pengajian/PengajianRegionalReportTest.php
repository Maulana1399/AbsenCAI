<?php

use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Models\desa;
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
    $response = $this->get(route('pengajian.report'));

    $response->assertRedirect(route('login'));
});

test('halaman report dapat diakses oleh user terverifikasi', function () {
    $event = pgm9_event();

    $user = User::factory()->create();
    app(ActiveEventContext::class)->set($event);

    $response = $this->actingAs($user)
        ->get(route('pengajian.report'));

    $response->assertOk();
});

test('halaman report tidak 500 ketika tidak ada active event', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('pengajian.report'));

    $response->assertOk();
    $response->assertSee('Tidak ada event aktif');
    $response->assertSee('Kelola Event');
});

test('halaman report dengan active event tetap berfungsi normal', function () {
    $event = pgm9_event();
    $desa = pgm9_desa();
    pgm9_person('Jono', 'L', $desa->id);

    $user = User::factory()->create();
    app(ActiveEventContext::class)->set($event);

    $response = $this->actingAs($user)
        ->get(route('pengajian.report'));

    $response->assertOk();
    $response->assertSee('Total Warga');
});
