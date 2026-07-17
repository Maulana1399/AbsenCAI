<?php

use App\Models\ActivityLog;
use App\Models\SuratIzin;
use App\Models\User;
use App\Models\peserta;
use App\Services\Attendance\SuratIzinService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->peserta = peserta::create([
        'nama'            => 'Peserta Print Test',
        'nip'             => 8001,
        'attendance_code' => 'KJA-PRINT-TEST',
        'jenis_kelamin'   => 'Laki - Laki',
    ]);
});

// ---------------------------------------------------------------------------
// Surat Izin print — activity log
// ---------------------------------------------------------------------------

test('surat izin print creates print_viewed activity log entry', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $this->user);

    $this->get(route('surat-izin.print', $surat->id));

    $this->assertDatabaseHas('activity_logs', [
        'action'       => 'print_viewed',
        'module'       => 'print',
        'subject_type' => SuratIzin::class,
        'subject_id'   => $surat->id,
        'user_id'      => $this->user->id,
    ]);
});

test('surat izin print stores correct properties', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);
    $surat->update(['status' => 'pending']);
    $surat = app(SuratIzinService::class)->approve($surat->fresh(), $this->user)['surat'];

    $this->get(route('surat-izin.print', $surat->id));

    $log = ActivityLog::where('module', 'print')
        ->where('action', 'print_viewed')
        ->where('subject_id', $surat->id)
        ->first();

    expect($log->properties)->toMatchArray([
        'print_type'    => 'surat_izin',
        'peserta_id'    => $this->peserta->id,
        'surat_izin_id' => $surat->id,
        'nomor_surat'   => $surat->nomor_surat,
    ]);
});

test('surat izin print stores correct description', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);
    $surat->update(['status' => 'pending']);
    $surat = app(SuratIzinService::class)->approve($surat->fresh(), $this->user)['surat'];

    $this->get(route('surat-izin.print', $surat->id));

    $log = ActivityLog::where('module', 'print')
        ->where('action', 'print_viewed')
        ->where('subject_id', $surat->id)
        ->first();

    expect($log->description)->toBe('Membuka tampilan cetak surat izin '.$surat->nomor_surat);
});

test('forbidden surat izin print does not create activity log entry', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Draft',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);

    $this->get(route('surat-izin.print', $surat->id))
        ->assertStatus(403);

    $this->assertDatabaseMissing('activity_logs', [
        'module'     => 'print',
        'action'     => 'print_viewed',
        'subject_id' => $surat->id,
    ]);
});

test('repeated surat izin print requests create separate activity log entries', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $this->user);

    $this->get(route('surat-izin.print', $surat->id));
    $this->get(route('surat-izin.print', $surat->id));

    $this->assertEquals(2, ActivityLog::where('module', 'print')
        ->where('action', 'print_viewed')
        ->where('subject_id', $surat->id)
        ->count());
});

test('surat izin print response remains successful', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $this->user);

    $this->get(route('surat-izin.print', $surat->id))
        ->assertStatus(200)
        ->assertSee($this->peserta->nama)
        ->assertSee($surat->nomor_surat);
});

// ---------------------------------------------------------------------------
// QR Label single print — activity log
// ---------------------------------------------------------------------------

test('qr label single print creates print_viewed activity log entry', function () {
    $this->get(route('qr-label.print.selected', $this->peserta->id));

    $this->assertDatabaseHas('activity_logs', [
        'action'       => 'print_viewed',
        'module'       => 'print',
        'subject_type' => peserta::class,
        'subject_id'   => $this->peserta->id,
        'user_id'      => $this->user->id,
    ]);
});

test('qr label single print stores correct properties', function () {
    $this->get(route('qr-label.print.selected', $this->peserta->id));

    $log = ActivityLog::where('module', 'print')
        ->where('action', 'print_viewed')
        ->where('subject_id', $this->peserta->id)
        ->first();

    expect($log->properties)->toMatchArray([
        'print_type'      => 'qr_label_single',
        'peserta_id'      => $this->peserta->id,
        'attendance_code' => $this->peserta->attendance_code,
    ]);
});

test('qr label single print response remains successful', function () {
    $this->get(route('qr-label.print.selected', $this->peserta->id))
        ->assertStatus(200)
        ->assertSee($this->peserta->nama);
});

// ---------------------------------------------------------------------------
// QR Label batch filtered print — activity log
// ---------------------------------------------------------------------------

test('qr label batch filtered print creates exactly one activity log entry', function () {
    peserta::create(['nama' => 'Peserta 2', 'nip' => 8002, 'attendance_code' => 'KJA-P2', 'jenis_kelamin' => 'Laki - Laki']);
    peserta::create(['nama' => 'Peserta 3', 'nip' => 8003, 'attendance_code' => 'KJA-P3', 'jenis_kelamin' => 'Perempuan']);

    $this->get(route('qr-label.print.filtered'));

    $this->assertEquals(1, ActivityLog::where('module', 'print')
        ->where('action', 'print_viewed')
        ->where('properties->print_type', 'qr_label_filtered')
        ->count());
});

test('qr label batch filtered print stores correct count', function () {
    peserta::create(['nama' => 'Peserta 2', 'nip' => 8002, 'attendance_code' => 'KJA-P2', 'jenis_kelamin' => 'Laki - Laki']);
    peserta::create(['nama' => 'Peserta 3', 'nip' => 8003, 'attendance_code' => 'KJA-P3', 'jenis_kelamin' => 'Perempuan']);

    $this->get(route('qr-label.print.filtered'));

    $log = ActivityLog::where('module', 'print')
        ->where('action', 'print_viewed')
        ->where('properties->print_type', 'qr_label_filtered')
        ->first();

    expect($log->properties['count'])->toBe(3);
    expect($log->properties['format'])->toBe('4x4_single');
});

test('qr label batch filtered print response remains successful', function () {
    peserta::create(['nama' => 'Peserta 2', 'nip' => 8002, 'attendance_code' => 'KJA-P2', 'jenis_kelamin' => 'Laki - Laki']);

    $this->get(route('qr-label.print.filtered'))
        ->assertStatus(200);
});

// ---------------------------------------------------------------------------
// QR Label batch A4 print — activity log
// ---------------------------------------------------------------------------

test('qr label batch a4 print creates exactly one activity log entry', function () {
    peserta::create(['nama' => 'Peserta 2', 'nip' => 8002, 'attendance_code' => 'KJA-P2', 'jenis_kelamin' => 'Laki - Laki']);
    peserta::create(['nama' => 'Peserta 3', 'nip' => 8003, 'attendance_code' => 'KJA-P3', 'jenis_kelamin' => 'Perempuan']);

    $this->get(route('qr-label.print.a4'));

    $this->assertEquals(1, ActivityLog::where('module', 'print')
        ->where('action', 'print_viewed')
        ->where('properties->print_type', 'qr_label_a4')
        ->count());
});

test('qr label batch a4 print stores correct count and format', function () {
    peserta::create(['nama' => 'Peserta 2', 'nip' => 8002, 'attendance_code' => 'KJA-P2', 'jenis_kelamin' => 'Laki - Laki']);

    $this->get(route('qr-label.print.a4'));

    $log = ActivityLog::where('module', 'print')
        ->where('action', 'print_viewed')
        ->where('properties->print_type', 'qr_label_a4')
        ->first();

    expect($log->properties['count'])->toBe(2);
    expect($log->properties['format'])->toBe('a4_grid');
});

test('qr label batch a4 print response remains successful', function () {
    peserta::create(['nama' => 'Peserta 2', 'nip' => 8002, 'attendance_code' => 'KJA-P2', 'jenis_kelamin' => 'Laki - Laki']);

    $this->get(route('qr-label.print.a4'))
        ->assertStatus(200);
});

// ---------------------------------------------------------------------------
// No unrelated Activity Log behavior breaks
// ---------------------------------------------------------------------------

test('surat izin print does not affect surat izin lifecycle logs', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $this->user);

    $this->get(route('surat-izin.print', $surat->id));

    $createdLogs = ActivityLog::where('module', 'surat_izin')->where('action', 'created')->count();
    $approvedLogs = ActivityLog::where('module', 'surat_izin')->where('action', 'approved')->count();

    expect($createdLogs)->toBe(1);
    expect($approvedLogs)->toBe(1);
});
