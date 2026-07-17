<?php

use App\Models\Absensi;
use App\Models\IzinAbsensi;
use App\Models\SesiAbsensi;
use App\Models\SuratIzin;
use App\Models\User;
use App\Models\peserta;
use App\Services\Attendance\AttendanceExceptionService;
use App\Services\Attendance\SuratIzinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makePeserta(int $nip, string $code): peserta
{
    return peserta::create([
        'nama'            => 'Peserta ' . $nip,
        'nip'             => $nip,
        'attendance_code' => $code,
        'jenis_kelamin'   => 'Laki - Laki',
    ]);
}

function makeSesi(string $tanggal, string $nama = 'Sesi'): SesiAbsensi
{
    return SesiAbsensi::create([
        'nama_sesi' => $nama . ' ' . $tanggal,
        'tanggal'   => $tanggal,
        'aktif'     => true,
    ]);
}

function makeUser(): User
{
    return User::factory()->create();
}

function makeSurat(peserta $peserta, User $creator, array $overrides = []): SuratIzin
{
    return app(SuratIzinService::class)->create(array_merge([
        'peserta_id'      => $peserta->id,
        'alasan'          => 'Keperluan keluarga',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $overrides), $creator->id);
}

// ---------------------------------------------------------------------------
// create / submit
// ---------------------------------------------------------------------------

test('create produces a draft surat izin linked to peserta and creator', function () {
    $peserta = makePeserta(6001, 'KJA-SI001');
    $user    = makeUser();

    $surat = makeSurat($peserta, $user);

    expect($surat)->toBeInstanceOf(SuratIzin::class)
        ->and($surat->status)->toBe('draft')
        ->and($surat->peserta_id)->toBe($peserta->id)
        ->and($surat->created_by)->toBe($user->id)
        ->and($surat->nomor_surat)->toBeNull();
});

test('submit transitions draft to pending', function () {
    $peserta = makePeserta(6002, 'KJA-SI002');
    $user    = makeUser();
    $surat   = makeSurat($peserta, $user);

    $result = app(SuratIzinService::class)->submit($surat);

    expect($result->status)->toBe('pending');
});

test('submit rejects non-draft surat', function () {
    $peserta = makePeserta(6003, 'KJA-SI003');
    $user    = makeUser();
    $surat   = makeSurat($peserta, $user);
    $surat->update(['status' => 'pending']);

    expect(fn () => app(SuratIzinService::class)->submit($surat->fresh()))
        ->toThrow(ValidationException::class);
});

// ---------------------------------------------------------------------------
// approve — core behaviour
// ---------------------------------------------------------------------------

test('approve creates IzinAbsensi for sessions within date range', function () {
    $peserta  = makePeserta(6010, 'KJA-SI010');
    $user     = makeUser();
    $approver = makeUser();

    makeSesi('2026-07-20', 'Pagi');
    makeSesi('2026-07-21', 'Siang');
    makeSesi('2026-07-22', 'Sore');

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ]);
    $surat->update(['status' => 'pending']);

    $result = app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    expect($result['created'])->toHaveCount(2)
        ->and($result['sesi_found'])->toBe(2)
        ->and($result['skipped_hadir'])->toHaveCount(0)
        ->and($result['skipped_izin'])->toHaveCount(0);

    expect(IzinAbsensi::where('peserta_id', $peserta->id)->count())->toBe(2);
});

test('approved IzinAbsensi has source=surat_izin and correct surat_izin_id', function () {
    $peserta  = makePeserta(6011, 'KJA-SI011');
    $user     = makeUser();
    $approver = makeUser();

    makeSesi('2026-07-20', 'Pagi');

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ]);
    $surat->update(['status' => 'pending']);

    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    $izin = IzinAbsensi::where('peserta_id', $peserta->id)->first();

    expect($izin->source)->toBe('surat_izin')
        ->and($izin->surat_izin_id)->toBe($surat->id);
});

test('approve sets nomor_surat, approved_by and approved_at', function () {
    $peserta  = makePeserta(6012, 'KJA-SI012');
    $user     = makeUser();
    $approver = makeUser();

    makeSesi('2026-07-20', 'Pagi');

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ]);
    $surat->update(['status' => 'pending']);

    $result = app(SuratIzinService::class)->approve($surat->fresh(), $approver);
    $fresh  = $result['surat'];

    expect($fresh->status)->toBe('approved')
        ->and($fresh->nomor_surat)->not->toBeNull()
        ->and($fresh->approved_by)->toBe($approver->id)
        ->and($fresh->approved_at)->not->toBeNull();
});

test('approve skips and reports session where peserta already hadir', function () {
    $peserta  = makePeserta(6013, 'KJA-SI013');
    $user     = makeUser();
    $approver = makeUser();

    $sesi = makeSesi('2026-07-20', 'Pagi');

    Absensi::create([
        'nip'      => $peserta->nip,
        'nama'     => $peserta->nama,
        'jam_scan' => '2026-07-20 08:00:00',
        'sesi_id'  => $sesi->id,
    ]);

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ]);
    $surat->update(['status' => 'pending']);

    $result = app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    expect($result['skipped_hadir'])->toHaveCount(1)
        ->and($result['created'])->toHaveCount(0);

    expect(IzinAbsensi::where('peserta_id', $peserta->id)->count())->toBe(0);
});

test('approve skips and reports session where peserta already izin', function () {
    $peserta  = makePeserta(6014, 'KJA-SI014');
    $user     = makeUser();
    $approver = makeUser();

    $sesi = makeSesi('2026-07-20', 'Pagi');

    app(AttendanceExceptionService::class)->recordIzin(
        $peserta->id,
        $sesi->id,
        'manual',
    );

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ]);
    $surat->update(['status' => 'pending']);

    $result = app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    expect($result['skipped_izin'])->toHaveCount(1)
        ->and($result['created'])->toHaveCount(0);

    expect(IzinAbsensi::where('peserta_id', $peserta->id)->count())->toBe(1);
});

test('approve returns zero created when no sessions exist in range', function () {
    $peserta  = makePeserta(6015, 'KJA-SI015');
    $user     = makeUser();
    $approver = makeUser();

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ]);
    $surat->update(['status' => 'pending']);

    $result = app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    expect($result['created'])->toHaveCount(0)
        ->and($result['sesi_found'])->toBe(0)
        ->and($result['surat']->status)->toBe('approved');
});

test('double approve throws ValidationException', function () {
    $peserta  = makePeserta(6016, 'KJA-SI016');
    $user     = makeUser();
    $approver = makeUser();

    $surat = makeSurat($peserta, $user);
    $surat->update(['status' => 'pending']);

    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    expect(fn () => app(SuratIzinService::class)->approve($surat->fresh(), $approver))
        ->toThrow(ValidationException::class);
});

// ---------------------------------------------------------------------------
// reject
// ---------------------------------------------------------------------------

test('reject transitions pending surat to rejected without creating IzinAbsensi', function () {
    $peserta = makePeserta(6020, 'KJA-SI020');
    $user    = makeUser();

    makeSesi('2026-07-20', 'Pagi');

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ]);
    $surat->update(['status' => 'pending']);

    $result = app(SuratIzinService::class)->reject($surat->fresh());

    expect($result->status)->toBe('rejected');
    expect(IzinAbsensi::count())->toBe(0);
});

test('reject throws when surat is not pending', function () {
    $peserta = makePeserta(6021, 'KJA-SI021');
    $user    = makeUser();

    $surat = makeSurat($peserta, $user);

    expect(fn () => app(SuratIzinService::class)->reject($surat))
        ->toThrow(ValidationException::class);
});

// ---------------------------------------------------------------------------
// markReturned
// ---------------------------------------------------------------------------

test('markReturned sets returned_at on approved surat', function () {
    $peserta  = makePeserta(6030, 'KJA-SI030');
    $user     = makeUser();
    $approver = makeUser();

    $surat = makeSurat($peserta, $user);
    $surat->update(['status' => 'pending']);

    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    $returned = app(SuratIzinService::class)->markReturned($surat->fresh());

    expect($returned->returned_at)->not->toBeNull()
        ->and($returned->isReturned())->toBeTrue();
});

test('markReturned throws when surat is not approved', function () {
    $peserta = makePeserta(6031, 'KJA-SI031');
    $user    = makeUser();
    $surat   = makeSurat($peserta, $user);

    expect(fn () => app(SuratIzinService::class)->markReturned($surat))
        ->toThrow(ValidationException::class);
});

test('markReturned throws if already marked returned', function () {
    $peserta  = makePeserta(6032, 'KJA-SI032');
    $user     = makeUser();
    $approver = makeUser();

    $surat = makeSurat($peserta, $user);
    $surat->update(['status' => 'pending']);

    app(SuratIzinService::class)->approve($surat->fresh(), $approver);
    app(SuratIzinService::class)->markReturned($surat->fresh());

    expect(fn () => app(SuratIzinService::class)->markReturned($surat->fresh()))
        ->toThrow(ValidationException::class);
});

// ---------------------------------------------------------------------------
// backward-compat: manual izin stays independent
// ---------------------------------------------------------------------------

test('manual izin via AttendanceExceptionService remains surat_izin_id null', function () {
    $peserta = makePeserta(6040, 'KJA-SI040');
    $sesi    = makeSesi('2026-07-20', 'Pagi');

    $izin = app(AttendanceExceptionService::class)->recordIzin($peserta->id, $sesi->id);

    expect($izin->source)->toBe('manual')
        ->and($izin->surat_izin_id)->toBeNull();
});
