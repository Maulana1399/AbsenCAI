<?php

use App\Models\Absensi;
use App\Models\IzinAbsensi;
use App\Models\peserta;
use App\Models\SesiAbsensi;
use App\Models\SuratIzin;
use App\Models\User;
use App\Services\Attendance\AttendanceExceptionService;
use App\Services\Attendance\SuratIzinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(Tests\TestCase::class, RefreshDatabase::class);

function makePeserta(int $id, string $code): peserta
{
    return peserta::create([
        'nama' => 'Peserta '.$id,
        'attendance_code' => $code,
        'jenis_kelamin' => 'Laki - Laki',
    ]);
}

function makeSesi(string $tanggal, string $nama = 'Sesi'): SesiAbsensi
{
    return SesiAbsensi::create([
        'nama_sesi' => $nama.' '.$tanggal,
        'tanggal' => $tanggal,
        'aktif' => true,
    ]);
}

function makeUser(): User
{
    return User::factory()->create();
}

function makeSurat(peserta $peserta, User $creator, array $overrides = []): SuratIzin
{
    return app(SuratIzinService::class)->create(array_merge([
        'peserta_id' => $peserta->id,
        'alasan' => 'Keperluan keluarga',
        'jenis_izin' => 'pulang',
        'tanggal_mulai' => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $overrides), $creator->id);
}

// ---------------------------------------------------------------------------
// create / submit
// ---------------------------------------------------------------------------

test('create produces a draft surat izin linked to peserta and creator', function () {
    $peserta = makePeserta(6001, 'KJA-SI001');
    $user = makeUser();

    $surat = makeSurat($peserta, $user);

    expect($surat)->toBeInstanceOf(SuratIzin::class)
        ->and($surat->status)->toBe('draft')
        ->and($surat->peserta_id)->toBe($peserta->id)
        ->and($surat->created_by)->toBe($user->id)
        ->and($surat->nomor_surat)->toBeNull()
        ->and($surat->jenis_izin)->toBe('pulang');
});

test('create accepts jenis_izin pulang', function () {
    $peserta = makePeserta(6001, 'KJA-SI001');
    $user = makeUser();

    $surat = makeSurat($peserta, $user, ['jenis_izin' => 'pulang']);

    expect($surat->jenis_izin)->toBe('pulang');
});

test('create accepts jenis_izin keluar', function () {
    $peserta = makePeserta(6001, 'KJA-SI001');
    $user = makeUser();

    $surat = makeSurat($peserta, $user, ['jenis_izin' => 'keluar']);

    expect($surat->jenis_izin)->toBe('keluar');
});

test('submit transitions draft to pending', function () {
    $peserta = makePeserta(6002, 'KJA-SI002');
    $user = makeUser();
    $surat = makeSurat($peserta, $user);

    $result = app(SuratIzinService::class)->submit($surat);

    expect($result->status)->toBe('pending');
});

test('submit rejects non-draft surat', function () {
    $peserta = makePeserta(6003, 'KJA-SI003');
    $user = makeUser();
    $surat = makeSurat($peserta, $user);
    $surat->update(['status' => 'pending']);

    expect(fn () => app(SuratIzinService::class)->submit($surat->fresh()))
        ->toThrow(ValidationException::class);
});

// ---------------------------------------------------------------------------
// approve — core behaviour
// ---------------------------------------------------------------------------

test('approve creates IzinAbsensi for sessions within date range', function () {
    $peserta = makePeserta(6010, 'KJA-SI010');
    $user = makeUser();
    $approver = makeUser();

    makeSesi('2026-07-20', 'Pagi');
    makeSesi('2026-07-21', 'Siang');
    makeSesi('2026-07-22', 'Sore');

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai' => '2026-07-20',
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
    $peserta = makePeserta(6011, 'KJA-SI011');
    $user = makeUser();
    $approver = makeUser();

    makeSesi('2026-07-20', 'Pagi');

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai' => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ]);
    $surat->update(['status' => 'pending']);

    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    $izin = IzinAbsensi::where('peserta_id', $peserta->id)->first();

    expect($izin->source)->toBe('surat_izin')
        ->and($izin->surat_izin_id)->toBe($surat->id);
});

test('approve sets nomor_surat, approved_by and approved_at', function () {
    $peserta = makePeserta(6012, 'KJA-SI012');
    $user = makeUser();
    $approver = makeUser();

    makeSesi('2026-07-20', 'Pagi');

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai' => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ]);
    $surat->update(['status' => 'pending']);

    $result = app(SuratIzinService::class)->approve($surat->fresh(), $approver);
    $fresh = $result['surat'];

    expect($fresh->status)->toBe('approved')
        ->and($fresh->nomor_surat)->not->toBeNull()
        ->and($fresh->approved_by)->toBe($approver->id)
        ->and($fresh->approved_at)->not->toBeNull();
});

test('approve skips and reports session where peserta already hadir', function () {
    $peserta = makePeserta(6013, 'KJA-SI013');
    $person = \App\Models\Person::create(['nama' => 'Peserta 6013', 'jenis_kelamin' => 'L']);
    $event = \App\Models\Event::create(['name' => 'SI Event', 'slug' => 'si-event', 'status' => 'active']);
    $participation = \App\Models\Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    \App\Models\LegacyPesertaMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id]);
    \App\Models\LegacyParticipationMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    $user = makeUser();
    $approver = makeUser();

    $sesi = makeSesi('2026-07-20', 'Pagi');
    $sesi->update(['event_id' => $event->id]);
    app(\App\Support\ActiveEventContext::class)->set($event);

    \App\Models\EventAttendance::create([
        'participation_id' => $participation->id,
        'sesi_absensi_id' => $sesi->id,
        'event_id' => $event->id,
        'status' => \App\Models\EventAttendance::STATUS_HADIR,
        'attended_at' => now(),
        'method' => 'scan',
    ]);

    expect(fn () => makeSurat($peserta, $user, [
        'tanggal_mulai' => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ]))->toThrow(
        \Illuminate\Validation\ValidationException::class,
        'Peserta sudah melakukan absensi sehingga surat izin tidak dapat dibuat.',
    );
});

test('approve skips and reports session where peserta already izin', function () {
    $peserta = makePeserta(6014, 'KJA-SI014');
    $user = makeUser();
    $approver = makeUser();

    $sesi = makeSesi('2026-07-20', 'Pagi');

    app(AttendanceExceptionService::class)->recordIzin(
        $peserta->id,
        $sesi->id,
        'manual',
    );

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai' => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ]);
    $surat->update(['status' => 'pending']);

    $result = app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    expect($result['skipped_izin'])->toHaveCount(1)
        ->and($result['created'])->toHaveCount(0);

    expect(IzinAbsensi::where('peserta_id', $peserta->id)->count())->toBe(1);
});

test('approve returns zero created when no sessions exist in range', function () {
    $peserta = makePeserta(6015, 'KJA-SI015');
    $user = makeUser();
    $approver = makeUser();

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai' => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ]);
    $surat->update(['status' => 'pending']);

    $result = app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    expect($result['created'])->toHaveCount(0)
        ->and($result['sesi_found'])->toBe(0)
        ->and($result['surat']->status)->toBe('approved');
});

test('double approve throws ValidationException', function () {
    $peserta = makePeserta(6016, 'KJA-SI016');
    $user = makeUser();
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
    $user = makeUser();

    makeSesi('2026-07-20', 'Pagi');

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai' => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ]);
    $surat->update(['status' => 'pending']);

    $result = app(SuratIzinService::class)->reject($surat->fresh());

    expect($result->status)->toBe('rejected');
    expect(IzinAbsensi::count())->toBe(0);
});

test('reject throws when surat is not pending', function () {
    $peserta = makePeserta(6021, 'KJA-SI021');
    $user = makeUser();

    $surat = makeSurat($peserta, $user);

    expect(fn () => app(SuratIzinService::class)->reject($surat))
        ->toThrow(ValidationException::class);
});

// ---------------------------------------------------------------------------
// markReturned
// ---------------------------------------------------------------------------

test('markReturned sets returned_at on approved surat with given date', function () {
    $peserta = makePeserta(6030, 'KJA-SI030');
    $user = makeUser();
    $approver = makeUser();

    $surat = makeSurat($peserta, $user);
    $surat->update(['status' => 'pending']);

    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    $returned = app(SuratIzinService::class)->markReturned($surat->fresh(), '2026-07-20');

    expect($returned->returned_at)->not->toBeNull()
        ->and($returned->returned_at->format('Y-m-d'))->toBe('2026-07-20')
        ->and($returned->isReturned())->toBeTrue();
});

test('markReturned throws when surat is not approved', function () {
    $peserta = makePeserta(6031, 'KJA-SI031');
    $user = makeUser();
    $surat = makeSurat($peserta, $user);

    expect(fn () => app(SuratIzinService::class)->markReturned($surat, '2026-07-20'))
        ->toThrow(ValidationException::class);
});

test('markReturned throws if already marked returned', function () {
    $peserta = makePeserta(6032, 'KJA-SI032');
    $user = makeUser();
    $approver = makeUser();

    $surat = makeSurat($peserta, $user);
    $surat->update(['status' => 'pending']);

    app(SuratIzinService::class)->approve($surat->fresh(), $approver);
    app(SuratIzinService::class)->markReturned($surat->fresh(), '2026-07-20');

    expect(fn () => app(SuratIzinService::class)->markReturned($surat->fresh(), '2026-07-21'))
        ->toThrow(ValidationException::class);
});

test('markReturned validates return date is within surat range', function () {
    $peserta = makePeserta(6033, 'KJA-SI033');
    $user = makeUser();
    $approver = makeUser();

    $surat = makeSurat($peserta, $user, [
        'tanggal_mulai' => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ]);
    $surat->update(['status' => 'pending']);

    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    expect(fn () => app(SuratIzinService::class)->markReturned($surat->fresh(), '2026-07-19'))
        ->toThrow(ValidationException::class);

    expect(fn () => app(SuratIzinService::class)->markReturned($surat->fresh(), '2026-07-22'))
        ->toThrow(ValidationException::class);
});

test('markReturned removes surat-generated izin for sessions on and after return date', function () {
    $peserta = makePeserta(6034, 'KJA-SI034');
    $user = makeUser();
    $approver = makeUser();

    $sesi17 = makeSesi('2026-07-17', 'Pagi');
    $sesi18 = makeSesi('2026-07-18', 'Siang');
    $sesi19 = makeSesi('2026-07-19', 'Sore');
    $sesi20 = makeSesi('2026-07-20', 'Malam');

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Keperluan keluarga',
        'tanggal_mulai' => '2026-07-17',
        'tanggal_selesai' => '2026-07-20',
    ], $user->id);
    $surat->update(['status' => 'pending']);

    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    expect(IzinAbsensi::where('surat_izin_id', $surat->id)->count())->toBe(4);

    app(SuratIzinService::class)->markReturned($surat->fresh(), '2026-07-19');

    $remaining = IzinAbsensi::where('surat_izin_id', $surat->id)->get();
    expect($remaining)->toHaveCount(2);

    $remainingSesiIds = $remaining->pluck('sesi_id')->toArray();
    expect($remainingSesiIds)->toContain($sesi17->id, $sesi18->id)
        ->and($remainingSesiIds)->not->toContain($sesi19->id, $sesi20->id);
});

test('markReturned preserves historical surat-generated izin before return date', function () {
    $peserta = makePeserta(6035, 'KJA-SI035');
    $user = makeUser();
    $approver = makeUser();

    makeSesi('2026-07-17', 'Pagi');
    makeSesi('2026-07-18', 'Siang');
    makeSesi('2026-07-19', 'Sore');

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Keperluan keluarga',
        'tanggal_mulai' => '2026-07-17',
        'tanggal_selesai' => '2026-07-19',
    ], $user->id);
    $surat->update(['status' => 'pending']);

    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    app(SuratIzinService::class)->markReturned($surat->fresh(), '2026-07-19');

    $remaining = IzinAbsensi::where('surat_izin_id', $surat->id)->get();
    expect($remaining)->toHaveCount(2);
});

test('markReturned never deletes manual izin', function () {
    $peserta = makePeserta(6036, 'KJA-SI036');
    $user = makeUser();
    $approver = makeUser();

    $sesi19 = makeSesi('2026-07-19', 'Sore');
    $sesi20 = makeSesi('2026-07-20', 'Malam');

    // Manual izin occupies sesi20 which is on/after the return date.
    // If markReturned used peserta_id + date to delete, this would be removed.
    app(AttendanceExceptionService::class)->recordIzin(
        $peserta->id, $sesi20->id, 'manual',
    );

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Keperluan keluarga',
        'tanggal_mulai' => '2026-07-19',
        'tanggal_selesai' => '2026-07-20',
    ], $user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    // sesi20 was skipped (manual izin already exists).
    // Only sesi19 gets a surat-generated izin.
    expect(IzinAbsensi::where('peserta_id', $peserta->id)->count())->toBe(2);
    expect(IzinAbsensi::where('surat_izin_id', $surat->id)->count())->toBe(1);

    app(SuratIzinService::class)->markReturned($surat->fresh(), '2026-07-19');

    // markReturned scopes delete through $surat->izinAbsensis() i.e. surat_izin_id.
    // The surat-generated izin for sesi19 is deleted (tanggal 19/7 >= return date 19/7).
    // The manual izin for sesi20 has surat_izin_id = null so it is untouched.
    $allIzin = IzinAbsensi::where('peserta_id', $peserta->id)->get();
    expect($allIzin)->toHaveCount(1);
    expect($allIzin->first()->source)->toBe('manual');
});

test('markReturned never deletes izin from another surat', function () {
    $pesertaA = makePeserta(6037, 'KJA-SI037');
    $pesertaB = makePeserta(6038, 'KJA-SI038');
    $user = makeUser();
    $approver = makeUser();

    makeSesi('2026-07-19', 'Sore');
    makeSesi('2026-07-20', 'Malam');

    $suratA = app(SuratIzinService::class)->create([
        'peserta_id' => $pesertaA->id,
        'alasan' => 'Izin A',
        'tanggal_mulai' => '2026-07-19',
        'tanggal_selesai' => '2026-07-20',
    ], $user->id);
    $suratA->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($suratA->fresh(), $approver);

    $suratB = app(SuratIzinService::class)->create([
        'peserta_id' => $pesertaB->id,
        'alasan' => 'Izin B',
        'tanggal_mulai' => '2026-07-19',
        'tanggal_selesai' => '2026-07-20',
    ], $user->id);
    $suratB->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($suratB->fresh(), $approver);

    expect(IzinAbsensi::where('surat_izin_id', $suratA->id)->count())->toBe(2);
    expect(IzinAbsensi::where('surat_izin_id', $suratB->id)->count())->toBe(2);

    app(SuratIzinService::class)->markReturned($suratA->fresh(), '2026-07-19');

    // Both sesi19 and sesi20 have tanggal >= return date (2026-07-19),
    // so all Surat A izin records are deleted.
    expect(IzinAbsensi::where('surat_izin_id', $suratA->id)->count())->toBe(0);

    // Surat B: all izin untouched — deletion scope is surat_izin_id = $suratA->id.
    expect(IzinAbsensi::where('surat_izin_id', $suratB->id)->count())->toBe(2);
});

test('markReturned never affects Hadir records', function () {
    $peserta = makePeserta(6038, 'KJA-SI038');
    $user = makeUser();
    $approver = makeUser();

    $sesi = makeSesi('2026-07-20', 'Pagi');

    Absensi::create([
        'nip' => random_int(10000, 99999),
        'nama' => $peserta->nama,
        'jam_scan' => '2026-07-20 08:00:00',
        'sesi_id' => $sesi->id,
    ]);

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Keperluan keluarga',
        'tanggal_mulai' => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ], $user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    // approve skipped karena sudah hadir, jadi hanya 0 created
    // tetap cek bahwa markReturned tidak menghapus absensi
    expect(Absensi::count())->toBe(1);

    app(SuratIzinService::class)->markReturned($surat->fresh(), '2026-07-20');

    expect(Absensi::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// syncNewSession
// ---------------------------------------------------------------------------

test('syncNewSession creates izin for new session inside active approved surat range', function () {
    $peserta = makePeserta(6040, 'KJA-SI040');
    $user = makeUser();
    $approver = makeUser();

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Keperluan keluarga',
        'tanggal_mulai' => '2026-07-17',
        'tanggal_selesai' => '2026-07-20',
    ], $user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    $sesiBaru = makeSesi('2026-07-18', 'Baru');

    app(SuratIzinService::class)->syncNewSession($sesiBaru);

    expect(IzinAbsensi::where('peserta_id', $peserta->id)
        ->where('sesi_id', $sesiBaru->id)
        ->exists()
    )->toBeTrue();
});

test('syncNewSession does not create izin for session outside surat range', function () {
    $peserta = makePeserta(6041, 'KJA-SI041');
    $user = makeUser();
    $approver = makeUser();

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Keperluan keluarga',
        'tanggal_mulai' => '2026-07-17',
        'tanggal_selesai' => '2026-07-20',
    ], $user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    $sesiLuar = makeSesi('2026-07-25', 'Luar');

    app(SuratIzinService::class)->syncNewSession($sesiLuar);

    expect(IzinAbsensi::where('peserta_id', $peserta->id)->count())->toBe(0);
});

test('syncNewSession does not create izin for session on or after returned date', function () {
    $peserta = makePeserta(6042, 'KJA-SI042');
    $user = makeUser();
    $approver = makeUser();

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Keperluan keluarga',
        'tanggal_mulai' => '2026-07-17',
        'tanggal_selesai' => '2026-07-20',
    ], $user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    app(SuratIzinService::class)->markReturned($surat->fresh(), '2026-07-19');

    $sesi19 = makeSesi('2026-07-19', 'Setelah Kembali');

    app(SuratIzinService::class)->syncNewSession($sesi19);

    expect(IzinAbsensi::where('peserta_id', $peserta->id)
        ->where('sesi_id', $sesi19->id)
        ->exists()
    )->toBeFalse();
});

test('syncNewSession does not create izin when participant already hadir', function () {
    $peserta = makePeserta(6043, 'KJA-SI043');
    $person = \App\Models\Person::create(['nama' => 'Peserta 6043', 'jenis_kelamin' => 'L']);
    $event = \App\Models\Event::create(['name' => 'SI Event Sync', 'slug' => 'si-event-sync', 'status' => 'active']);
    $participation = \App\Models\Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'jenis_peserta' => 'Wajib']);
    \App\Models\LegacyPesertaMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id]);
    \App\Models\LegacyParticipationMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id, 'migrated_at' => now()]);

    $user = makeUser();
    $approver = makeUser();
    $sesiBaru = makeSesi('2026-07-18', 'Baru');
    $sesiBaru->update(['event_id' => $event->id]);
    app(\App\Support\ActiveEventContext::class)->set($event);

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Keperluan keluarga',
        'tanggal_mulai' => '2026-07-17',
        'tanggal_selesai' => '2026-07-20',
    ], $user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    // approve() already created EventAttendance IZIN for $sesiBaru.
    // syncNewSession must find it and skip — no IzinAbsensi should be created.
    app(SuratIzinService::class)->syncNewSession($sesiBaru);

    expect(IzinAbsensi::where('peserta_id', $peserta->id)
        ->where('sesi_id', $sesiBaru->id)
        ->exists()
    )->toBeFalse();
});

test('syncNewSession does not create izin when participant already has izin for that session', function () {
    $peserta = makePeserta(6044, 'KJA-SI044');
    $user = makeUser();
    $approver = makeUser();

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Keperluan keluarga',
        'tanggal_mulai' => '2026-07-17',
        'tanggal_selesai' => '2026-07-20',
    ], $user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $approver);

    $sesiBaru = makeSesi('2026-07-18', 'Baru');

    app(AttendanceExceptionService::class)->recordIzin(
        $peserta->id, $sesiBaru->id, 'manual',
    );

    app(SuratIzinService::class)->syncNewSession($sesiBaru);

    expect(IzinAbsensi::where('peserta_id', $peserta->id)
        ->where('sesi_id', $sesiBaru->id)
        ->where('source', 'surat_izin')
        ->exists()
    )->toBeFalse();
});

test('syncNewSession does nothing when surat is not approved', function () {
    $peserta = makePeserta(6045, 'KJA-SI045');
    $user = makeUser();

    app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Keperluan keluarga',
        'tanggal_mulai' => '2026-07-17',
        'tanggal_selesai' => '2026-07-20',
    ], $user->id);

    $sesiBaru = makeSesi('2026-07-18', 'Baru');

    app(SuratIzinService::class)->syncNewSession($sesiBaru);

    expect(IzinAbsensi::count())->toBe(0);
});

test('syncNewSession does nothing when surat is returned', function () {
    $peserta = makePeserta(6046, 'KJA-SI046');
    $user = makeUser();
    $approver = makeUser();

    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $peserta->id,
        'alasan' => 'Keperluan keluarga',
        'tanggal_mulai' => '2026-07-17',
        'tanggal_selesai' => '2026-07-20',
    ], $user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $approver);
    app(SuratIzinService::class)->markReturned($surat->fresh(), '2026-07-20');

    $countBefore = IzinAbsensi::where('surat_izin_id', $surat->id)->count();

    $sesiBaru = makeSesi('2026-07-17', 'Baru');

    app(SuratIzinService::class)->syncNewSession($sesiBaru);

    $countAfter = IzinAbsensi::where('surat_izin_id', $surat->id)->count();

    expect($countAfter)->toBe($countBefore);
    expect(IzinAbsensi::where('surat_izin_id', $surat->id)
        ->where('sesi_id', $sesiBaru->id)
        ->exists()
    )->toBeFalse();
});

// ---------------------------------------------------------------------------
// backward-compat: manual izin stays independent
// ---------------------------------------------------------------------------

test('manual izin via AttendanceExceptionService remains surat_izin_id null', function () {
    $peserta = makePeserta(6040, 'KJA-SI040');
    $sesi = makeSesi('2026-07-20', 'Pagi');

    $izin = app(AttendanceExceptionService::class)->recordIzin($peserta->id, $sesi->id);

    expect($izin->source)->toBe('manual')
        ->and($izin->surat_izin_id)->toBeNull();
});
