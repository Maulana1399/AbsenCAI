<?php

use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\Participation;
use App\Models\Person;
use App\Models\desa;
use App\Services\Pengajian\DesaAccessService;
use App\Services\Pengajian\PengajianAttendanceService;
use App\Services\Pengajian\PengajianDesaReportService;
use App\Services\Pengajian\PengajianIdentityService;
use Carbon\Carbon;
// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function pgm7r_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'Pengajian Report',
        'slug' => 'pengajian-report-'.str()->random(6),
        'status' => 'active',
    ], $overrides));
}

function pgm7r_desa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Report '.str()->random(4),
    ], $overrides));
}

function pgm7r_grant(Event $event, desa $desa): DesaAccessGrant
{
    $now = Carbon::now();
    $result = app(DesaAccessService::class)->createGrant(
        $event, $desa,
        $now->copy()->subHour(),
        $now->copy()->addHour(),
    );
    return $result['grant'];
}

function pgm7r_person(string $nama, string $gender = 'L', ?int $desaId = null, ?string $birthDate = null): Person
{
    return Person::create([
        'nama' => $nama,
        'jenis_kelamin' => $gender,
        'desa_id' => $desaId,
        'tanggal_lahir' => $birthDate,
    ]);
}

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------

test('Total Person hanya scoped Desa', function () {
    $event = pgm7r_event();
    $desaA = pgm7r_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm7r_desa(['desa_asal' => 'Desa B']);
    $grant = pgm7r_grant($event, $desaA);

    pgm7r_person('Jono A', 'L', $desaA->id);
    pgm7r_person('Joni A', 'L', $desaA->id);
    pgm7r_person('Jono B', 'L', $desaB->id);

    $summary = app(PengajianDesaReportService::class)->summary($grant);

    expect($summary['total_warga'])->toBe(2);
});

test('Sudah Hadir hanya scoped Event + Desa', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    $hadir = pgm7r_person('Jono Hadir', 'L', $desa->id);
    pgm7r_person('Jono Belum', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $hadir, $grant,
    );

    $summary = app(PengajianDesaReportService::class)->summary($grant);

    expect($summary['sudah_hadir'])->toBe(1);
});

test('Belum Hadir dihitung benar', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    $hadir = pgm7r_person('Jono Hadir', 'L', $desa->id);
    pgm7r_person('Jono Belum', 'L', $desa->id);
    pgm7r_person('Joni Belum', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $hadir, $grant,
    );

    $summary = app(PengajianDesaReportService::class)->summary($grant);

    expect($summary['total_warga'])->toBe(3);
    expect($summary['sudah_hadir'])->toBe(1);
    expect($summary['belum_hadir'])->toBe(2);
});

test('Self count benar', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    $self = pgm7r_person('Jono Self', 'L', $desa->id, '2000-01-15');
    app(PengajianAttendanceService::class)->attendPersonPublicContext(
        $self, $grant,
    );

    $summary = app(PengajianDesaReportService::class)->summary($grant);

    expect($summary['self'])->toBe(1);
    expect($summary['operator'])->toBe(0);
});

test('Operator count benar', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    $op = pgm7r_person('Jono Op', 'L', $desa->id);
    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $op, $grant,
    );

    $summary = app(PengajianDesaReportService::class)->summary($grant);

    expect($summary['operator'])->toBe(1);
    expect($summary['self'])->toBe(0);
});

test('Attendance Event lain tidak dihitung di summary', function () {
    $event1 = pgm7r_event(['name' => 'Event 1']);
    $event2 = pgm7r_event(['name' => 'Event 2']);
    $desa = pgm7r_desa();
    $grant1 = pgm7r_grant($event1, $desa);
    $grant2 = pgm7r_grant($event2, $desa);

    $person = pgm7r_person('Jono', 'L', $desa->id);
    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant1,
    );

    $summary = app(PengajianDesaReportService::class)->summary($grant2);

    expect($summary['sudah_hadir'])->toBe(0);
});

test('Attendance Desa lain tidak dihitung di summary', function () {
    $event = pgm7r_event();
    $desaA = pgm7r_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm7r_desa(['desa_asal' => 'Desa B']);
    $grantA = pgm7r_grant($event, $desaA);
    $grantB = pgm7r_grant($event, $desaB);

    $personA = pgm7r_person('Jono A', 'L', $desaA->id);
    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $personA, $grantA,
    );
    pgm7r_person('Jono B', 'L', $desaB->id);

    $summaryB = app(PengajianDesaReportService::class)->summary($grantB);

    expect($summaryB['sudah_hadir'])->toBe(0);
});

test('Same Person Event lain tidak memengaruhi current Event summary', function () {
    $event1 = pgm7r_event(['name' => 'Event 1']);
    $event2 = pgm7r_event(['name' => 'Event 2']);
    $desa = pgm7r_desa();
    $grant1 = pgm7r_grant($event1, $desa);
    $grant2 = pgm7r_grant($event2, $desa);

    $person = pgm7r_person('Jono', 'L', $desa->id);
    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant1,
    );

    $summary2 = app(PengajianDesaReportService::class)->summary($grant2);

    expect($summary2['sudah_hadir'])->toBe(0);
    expect($summary2['belum_hadir'])->toBe($summary2['total_warga']);
});

test('Duplicate attendance tidak menggandakan count', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);
    $person = pgm7r_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    expect(fn () => app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    ))->toThrow(\RuntimeException::class);

    $summary = app(PengajianDesaReportService::class)->summary($grant);

    expect($summary['sudah_hadir'])->toBe(1);
});

// ---------------------------------------------------------------------------
// Attendance list
// ---------------------------------------------------------------------------

test('List hanya Person scoped Desa', function () {
    $event = pgm7r_event();
    $desaA = pgm7r_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm7r_desa(['desa_asal' => 'Desa B']);
    $grant = pgm7r_grant($event, $desaA);

    pgm7r_person('Jono A', 'L', $desaA->id);
    pgm7r_person('Jono B', 'L', $desaB->id);

    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    expect($list)->toHaveCount(1)
        ->and($list[0]['nama'])->toBe('Jono A');
});

test('Hadir menampilkan attended_at', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);
    $person = pgm7r_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    expect($list)->toHaveCount(1);
    expect($list[0]['hadir'])->toBeTrue();
    expect($list[0]['attended_at'])->not->toBeNull();
});

test('Belum hadir memiliki status benar', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    pgm7r_person('Jono', 'L', $desa->id);

    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    expect($list)->toHaveCount(1);
    expect($list[0]['hadir'])->toBeFalse();
    expect($list[0]['attended_at'])->toBeNull();
});

test('Method self ditampilkan benar di list', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);
    $person = pgm7r_person('Jono', 'L', $desa->id, '2000-01-15');

    app(PengajianAttendanceService::class)->attendPersonPublicContext(
        $person, $grant,
    );

    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    expect($list[0]['method'])->toBe('self');
});

test('Method operator ditampilkan benar di list', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);
    $person = pgm7r_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    expect($list[0]['method'])->toBe('operator');
});

test('Search nama scoped Desa di list', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    pgm7r_person('Jono Spesifik', 'L', $desa->id);
    pgm7r_person('Joni Lain', 'L', $desa->id);

    $list = app(PengajianDesaReportService::class)->attendanceList(
        $grant, search: 'Spesifik',
    );

    expect($list)->toHaveCount(1)
        ->and($list[0]['nama'])->toBe('Jono Spesifik');
});

test('Filter hadir bekerja di list', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    $hadir = pgm7r_person('Jono Hadir', 'L', $desa->id);
    pgm7r_person('Jono Belum', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $hadir, $grant,
    );

    $list = app(PengajianDesaReportService::class)->attendanceList(
        $grant, status: 'hadir',
    );

    expect($list)->toHaveCount(1)
        ->and($list[0]['nama'])->toBe('Jono Hadir');
});

test('Filter method bekerja di list', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    $op = pgm7r_person('Jono Op', 'L', $desa->id);
    $selfPerson = pgm7r_person('Jono Self', 'L', $desa->id, '2000-01-15');

    app(PengajianAttendanceService::class)->attendPersonOperatorContext($op, $grant);
    app(PengajianAttendanceService::class)->attendPersonPublicContext($selfPerson, $grant);

    $list = app(PengajianDesaReportService::class)->attendanceList(
        $grant, method: 'operator',
    );

    expect($list)->toHaveCount(1)
        ->and($list[0]['method'])->toBe('operator');
});

test('NIP tidak terekspos di list', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);
    $person = pgm7r_person('Jono', 'L', $desa->id);

    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    expect($list[0])->not->toHaveKey('nip');
});

test('attendance_code tidak terekspos di list', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);
    $person = pgm7r_person('Jono', 'L', $desa->id);

    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    expect($list[0])->not->toHaveKey('attendance_code');
});

// ===========================================================================
// PGM.12 — Operator Search
// ===========================================================================

test('Operator search menemukan Person dari desa yang sama', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    pgm7r_person('Jono Target', 'L', $desa->id);

    $results = app(PengajianIdentityService::class)
        ->searchPersons($grant, 'Jono');

    expect($results)->toHaveCount(1)
        ->and($results[0]['nama'])->toBe('Jono Target');
});

test('Operator search partial nama case-insensitive', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    pgm7r_person('Supriyanto', 'L', $desa->id);

    $results = app(PengajianIdentityService::class)
        ->searchPersons($grant, 'pri');

    expect($results)->toHaveCount(1)
        ->and($results[0]['nama'])->toBe('Supriyanto');
});

test('Operator search tidak mengembalikan Person dari Desa lain', function () {
    $event = pgm7r_event();
    $desaA = pgm7r_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm7r_desa(['desa_asal' => 'Desa B']);
    $grant = pgm7r_grant($event, $desaA);

    pgm7r_person('Jono Desa A', 'L', $desaA->id);
    pgm7r_person('Jono Desa B', 'L', $desaB->id);

    $results = app(PengajianIdentityService::class)
        ->searchPersons($grant, 'Jono');

    expect($results)->toHaveCount(1)
        ->and($results[0]['nama'])->toBe('Jono Desa A');
});

test('Operator search menemukan Person tanpa Participation', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    pgm7r_person('Jono Baru', 'L', $desa->id);

    $results = app(PengajianIdentityService::class)
        ->searchPersons($grant, 'Jono');

    expect($results)->toHaveCount(1)
        ->and($results[0]['nama'])->toBe('Jono Baru');
});

test('Operator search query kurang dari 3 karakter mengembalikan kosong', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    pgm7r_person('Jono', 'L', $desa->id);

    $results = app(PengajianIdentityService::class)
        ->searchPersons($grant, 'Jo');

    expect($results)->toHaveCount(0);
});

// ===========================================================================
// PGM.12 — Operator Attendance
// ===========================================================================

test('Operator attendance membuat Participation jika belum ada', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);
    $person = pgm7r_person('Jono', 'L', $desa->id);

    expect(Participation::count())->toBe(0);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    expect(Participation::count())->toBe(1);
    expect(Participation::first()->event_id)->toBe($event->id);
});

test('Operator attendance method adalah operator', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);
    $person = pgm7r_person('Jono', 'L', $desa->id);

    $attendance = app(PengajianAttendanceService::class)
        ->attendPersonOperatorContext($person, $grant);

    expect($attendance->method)->toBe('operator');
});

test('Duplicate operator attendance ditolak', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);
    $person = pgm7r_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    expect(fn () => app(PengajianAttendanceService::class)
        ->attendPersonOperatorContext($person, $grant))
        ->toThrow(\RuntimeException::class, 'sudah tercatat hadir');
});

// ===========================================================================
// PGM.12 — Attendance List Consistency
// ===========================================================================

test('Attendance list menampilkan self attendance', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    $person = pgm7r_person('Jono Self', 'L', $desa->id, '2000-01-15');
    app(PengajianAttendanceService::class)->attendPersonPublicContext(
        $person, $grant,
    );

    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    expect($list)->toHaveCount(1);
    expect($list[0]['hadir'])->toBeTrue();
    expect($list[0]['method'])->toBe('self');
});

test('Attendance list menampilkan operator attendance', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    $person = pgm7r_person('Jono Op', 'L', $desa->id);
    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    expect($list)->toHaveCount(1);
    expect($list[0]['hadir'])->toBeTrue();
    expect($list[0]['method'])->toBe('operator');
});

test('Filter Hadir + Self hanya menampilkan self attendance', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    $self = pgm7r_person('Jono Self', 'L', $desa->id, '2000-01-15');
    $op = pgm7r_person('Jono Op', 'L', $desa->id);
    pgm7r_person('Jono Belum', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonPublicContext($self, $grant);
    app(PengajianAttendanceService::class)->attendPersonOperatorContext($op, $grant);

    $list = app(PengajianDesaReportService::class)->attendanceList(
        $grant, status: 'hadir', method: 'self',
    );

    expect($list)->toHaveCount(1)
        ->and($list[0]['nama'])->toBe('Jono Self')
        ->and($list[0]['method'])->toBe('self');
});

test('Summary dan list menggunakan scope event dan desa yang sama', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    $self = pgm7r_person('Jono Self', 'L', $desa->id, '2000-01-15');
    $op = pgm7r_person('Jono Op', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonPublicContext($self, $grant);
    app(PengajianAttendanceService::class)->attendPersonOperatorContext($op, $grant);

    $summary = app(PengajianDesaReportService::class)->summary($grant);
    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    $hadirInList = collect($list)->where('hadir', true)->count();

    expect($summary['sudah_hadir'])->toBe(2);
    expect($hadirInList)->toBe(2);
    expect($summary['self'])->toBe(1);
    expect($summary['operator'])->toBe(1);
});

test('Cross-Event attendance tidak muncul di list', function () {
    $event1 = pgm7r_event(['name' => 'Event Pilot']);
    $event2 = pgm7r_event(['name' => 'Event Lain']);
    $desa = pgm7r_desa();
    $grant1 = pgm7r_grant($event1, $desa);
    $grant2 = pgm7r_grant($event2, $desa);

    $person = pgm7r_person('Jono', 'L', $desa->id);
    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant1,
    );

    $list = app(PengajianDesaReportService::class)->attendanceList($grant2);

    $hadirInList = collect($list)->where('hadir', true)->count();
    expect($hadirInList)->toBe(0);
});

test('Cross-Desa attendance tidak muncul di list', function () {
    $event = pgm7r_event();
    $desaA = pgm7r_desa(['desa_asal' => 'Desa A']);
    $desaB = pgm7r_desa(['desa_asal' => 'Desa B']);
    $grantA = pgm7r_grant($event, $desaA);
    $grantB = pgm7r_grant($event, $desaB);

    $personA = pgm7r_person('Jono A', 'L', $desaA->id);
    $personB = pgm7r_person('Jono B', 'L', $desaB->id);
    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $personA, $grantA,
    );
    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $personB, $grantB,
    );

    $listA = app(PengajianDesaReportService::class)->attendanceList($grantA);

    expect($listA)->toHaveCount(1);
    expect($listA[0]['nama'])->toBe('Jono A');
    expect($listA[0]['hadir'])->toBeTrue();
});

// ===========================================================================
// PGM.15 — attended_at format from raw join regression
// ===========================================================================

test('attendanceList attended_at diformat dengan benar dari raw join', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);
    $person = pgm7r_person('Jono', 'L', $desa->id);

    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $person, $grant,
    );

    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    expect($list)->toHaveCount(1);
    expect($list[0]['hadir'])->toBeTrue();
    expect($list[0]['attended_at'])->toMatch('/^\d{2} [A-Za-z]{3} \d{4} \d{2}:\d{2}$/');
    expect($list[0]['method'])->toBe('operator');
});

test('attendanceList untuk peserta belum hadir memberikan null attended_at', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);
    pgm7r_person('Jono', 'L', $desa->id);

    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    expect($list)->toHaveCount(1);
    expect($list[0]['hadir'])->toBeFalse();
    expect($list[0]['attended_at'])->toBeNull();
    expect($list[0]['method'])->toBeNull();
});

test('attendanceList dengan multiple peserta dan attendance menghasilkan attended_at yang benar', function () {
    $event = pgm7r_event();
    $desa = pgm7r_desa();
    $grant = pgm7r_grant($event, $desa);

    $hadir = pgm7r_person('Jono', 'L', $desa->id);
    pgm7r_person('Joni', 'L', $desa->id);
    app(PengajianAttendanceService::class)->attendPersonOperatorContext(
        $hadir, $grant,
    );

    $list = app(PengajianDesaReportService::class)->attendanceList($grant);

    expect($list)->toHaveCount(2);
    $hadirItem = collect($list)->firstWhere('hadir', true);
    expect($hadirItem['attended_at'])->toMatch('/^\d{2} [A-Za-z]{3} \d{4} \d{2}:\d{2}$/');
    $belumItem = collect($list)->firstWhere('hadir', false);
    expect($belumItem['attended_at'])->toBeNull();
});
