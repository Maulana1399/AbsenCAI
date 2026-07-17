<?php

use App\Models\ActivityLog;
use App\Models\IzinAbsensi;
use App\Models\SesiAbsensi;
use App\Models\SuratIzin;
use App\Models\User;
use App\Models\peserta;
use App\Services\Attendance\SuratIzinService;
use Illuminate\Validation\ValidationException;

function makeActivityLogPeserta(int $nip, string $code): peserta
{
    return peserta::create([
        'nama'            => 'Peserta ' . $nip,
        'nip'             => $nip,
        'attendance_code' => $code,
        'jenis_kelamin'   => 'Laki - Laki',
    ]);
}

function makeActivityLogSesi(string $tanggal, string $nama = 'Sesi'): SesiAbsensi
{
    return SesiAbsensi::create([
        'nama_sesi' => $nama . ' ' . $tanggal,
        'tanggal'   => $tanggal,
        'aktif'     => true,
    ]);
}

function makeActivityLogUser(): User
{
    return User::factory()->create();
}

function makeActivityLogSurat(peserta $peserta, User $creator, array $overrides = []): SuratIzin
{
    return app(SuratIzinService::class)->create(array_merge([
        'peserta_id'      => $peserta->id,
        'alasan'          => 'Keperluan keluarga',
        'jenis_izin'      => 'pulang',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $overrides), $creator->id);
}

// ---------------------------------------------------------------------------
// create produces activity log
// ---------------------------------------------------------------------------

test('create surat izin produces activity log', function () {
    $peserta = makeActivityLogPeserta(6001, 'KJA-SI001');
    $user    = makeActivityLogUser();
    $this->actingAs($user);

    $surat = makeActivityLogSurat($peserta, $user);

    $this->assertDatabaseHas('activity_logs', [
        'action'       => 'created',
        'module'       => 'surat_izin',
        'subject_type' => SuratIzin::class,
        'subject_id'   => $surat->id,
        'user_id'      => $user->id,
    ]);
});

// ---------------------------------------------------------------------------
// submit produces activity log
// ---------------------------------------------------------------------------

test('submit surat izin produces activity log', function () {
    $peserta = makeActivityLogPeserta(6002, 'KJA-SI002');
    $user    = makeActivityLogUser();
    $this->actingAs($user);
    $surat = makeActivityLogSurat($peserta, $user);

    app(SuratIzinService::class)->submit($surat);

    $this->assertDatabaseHas('activity_logs', [
        'action'       => 'submitted',
        'module'       => 'surat_izin',
        'subject_type' => SuratIzin::class,
        'subject_id'   => $surat->id,
        'user_id'      => $user->id,
    ]);
});

// ---------------------------------------------------------------------------
// approve produces activity log
// ---------------------------------------------------------------------------

test('approve surat izin produces activity log', function () {
    $peserta = makeActivityLogPeserta(6003, 'KJA-SI003');
    $creator = makeActivityLogUser();
    $approver = makeActivityLogUser();
    $this->actingAs($approver);
    makeActivityLogSesi('2026-07-20');
    makeActivityLogSesi('2026-07-21');

    $surat = makeActivityLogSurat($peserta, $creator);
    app(SuratIzinService::class)->submit($surat);
    app(SuratIzinService::class)->approve($surat, $approver);

    $this->assertDatabaseHas('activity_logs', [
        'action'       => 'approved',
        'module'       => 'surat_izin',
        'subject_type' => SuratIzin::class,
        'user_id'      => $approver->id,
    ]);
});

// ---------------------------------------------------------------------------
// reject produces activity log
// ---------------------------------------------------------------------------

test('reject surat izin produces activity log', function () {
    $peserta = makeActivityLogPeserta(6004, 'KJA-SI004');
    $creator = makeActivityLogUser();
    $rejector = makeActivityLogUser();
    $this->actingAs($rejector);

    $surat = makeActivityLogSurat($peserta, $creator);
    app(SuratIzinService::class)->submit($surat);
    app(SuratIzinService::class)->reject($surat);

    $this->assertDatabaseHas('activity_logs', [
        'action'       => 'rejected',
        'module'       => 'surat_izin',
        'subject_type' => SuratIzin::class,
        'subject_id'   => $surat->id,
        'user_id'      => $rejector->id,
    ]);
});

// ---------------------------------------------------------------------------
// markReturned produces activity log
// ---------------------------------------------------------------------------

test('markReturned produces activity log', function () {
    $peserta = makeActivityLogPeserta(6005, 'KJA-SI005');
    $creator = makeActivityLogUser();
    $approver = makeActivityLogUser();
    $this->actingAs($approver);
    makeActivityLogSesi('2026-07-20');
    makeActivityLogSesi('2026-07-21');

    $surat = makeActivityLogSurat($peserta, $creator);
    app(SuratIzinService::class)->submit($surat);
    app(SuratIzinService::class)->approve($surat, $approver);
    app(SuratIzinService::class)->markReturned($surat, '2026-07-21');

    $this->assertDatabaseHas('activity_logs', [
        'action'       => 'returned',
        'module'       => 'surat_izin',
        'subject_type' => SuratIzin::class,
        'subject_id'   => $surat->id,
        'user_id'      => $approver->id,
    ]);
});

// ---------------------------------------------------------------------------
// failed lifecycle transition does NOT produce false activity log
// ---------------------------------------------------------------------------

test('failed submit on already submitted surat does not create activity log', function () {
    $peserta = makeActivityLogPeserta(6006, 'KJA-SI006');
    $user    = makeActivityLogUser();
    $this->actingAs($user);
    $surat = makeActivityLogSurat($peserta, $user);

    app(SuratIzinService::class)->submit($surat);

    $this->expectException(ValidationException::class);
    app(SuratIzinService::class)->submit($surat);

    $this->assertDatabaseMissing('activity_logs', [
        'action' => 'submitted',
        'subject_id' => $surat->id,
    ]);
});

// ---------------------------------------------------------------------------
// failed transition does NOT leave false activity log
// ---------------------------------------------------------------------------

test('failed transition after successful operation does not duplicate activity log', function () {
    $peserta = makeActivityLogPeserta(6007, 'KJA-SI007');
    $creator = makeActivityLogUser();
    $approver = makeActivityLogUser();
    $this->actingAs($approver);
    makeActivityLogSesi('2026-07-20');
    makeActivityLogSesi('2026-07-21');

    $surat = makeActivityLogSurat($peserta, $creator);
    app(SuratIzinService::class)->submit($surat);
    app(SuratIzinService::class)->approve($surat, $approver);

    app(SuratIzinService::class)->markReturned($surat, '2026-07-21');

    $this->assertEquals(1, ActivityLog::where('action', 'returned')
        ->where('subject_id', $surat->id)
        ->count());

    try {
        app(SuratIzinService::class)->markReturned($surat, '2026-07-21');
    } catch (ValidationException $e) {
    }

    $this->assertEquals(1, ActivityLog::where('action', 'returned')
        ->where('subject_id', $surat->id)
        ->count());
});
