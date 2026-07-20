<?php

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SuratIzin;
use App\Models\User;
use App\Models\peserta;
use App\Services\Attendance\SuratIzinService;
use App\Support\ActiveEventContext;

function printLog_createMappedPeserta(Event $event, array $overrides = []): peserta
{
    $nip = $overrides['nip'] ?? fake()->unique()->numberBetween(9000, 9999);
    $name = $overrides['nama'] ?? 'Peserta '.$nip;
    $gender = $overrides['jenis_kelamin'] ?? 'Laki - Laki';
    $attendanceCode = $overrides['attendance_code'] ?? 'KJA-'.strtoupper(str()->random(8));

    $peserta = peserta::create([
        'nama' => $name,
        'nip' => (int) $nip,
        'attendance_code' => $attendanceCode,
        'jenis_kelamin' => $gender,
    ]);

    $person = Person::create([
        'nama' => $name,
        'nip' => (int) $nip,
        'jenis_kelamin' => $gender === 'Laki - Laki' ? 'L' : 'P',
    ]);

    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => $overrides['participant_number'] ?? 'KL'.str_pad((string) $peserta->id, 3, '0', STR_PAD_LEFT),
        'attendance_code' => $attendanceCode,
        'jenis_peserta' => $overrides['jenis_peserta'] ?? 'Wajib',
    ]);

    LegacyPesertaMapping::create([
        'peserta_id' => $peserta->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $event->id,
    ]);

    return $peserta;
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->peserta = peserta::create([
        'nama'            => 'Peserta Print Test',
        'nip'             => 8001,
        'attendance_code' => 'KJA-PRINT-TEST',
        'jenis_kelamin'   => 'Laki - Laki',
    ]);
    $this->event = Event::create([
        'name' => 'Print QR Event',
        'slug' => 'print-qr-event-'.str()->random(6),
        'status' => 'active',
    ]);
    $person = Person::create([
        'nama' => $this->peserta->nama,
        'nip' => $this->peserta->nip,
        'jenis_kelamin' => 'L',
    ]);
    $participation = Participation::create([
        'person_id' => $person->id,
        'event_id' => $this->event->id,
        'participant_number' => 'KL001',
        'attendance_code' => $this->peserta->attendance_code,
        'jenis_peserta' => 'Wajib',
    ]);
    LegacyPesertaMapping::create([
        'peserta_id' => $this->peserta->id,
        'person_id' => $person->id,
        'participation_id' => $participation->id,
        'event_id' => $this->event->id,
    ]);

    app(ActiveEventContext::class)->set($this->event);
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
        'subject_type' => App\Models\Person::class,
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
    printLog_createMappedPeserta($this->event, ['nama' => 'Peserta 2', 'nip' => 8002, 'attendance_code' => 'KJA-P2', 'jenis_kelamin' => 'Laki - Laki']);
    printLog_createMappedPeserta($this->event, ['nama' => 'Peserta 3', 'nip' => 8003, 'attendance_code' => 'KJA-P3', 'jenis_kelamin' => 'Perempuan']);

    $this->get(route('qr-label.print.filtered'));

    $this->assertEquals(1, ActivityLog::where('module', 'print')
        ->where('action', 'print_viewed')
        ->where('properties->print_type', 'qr_label_filtered')
        ->count());
});

test('qr label batch filtered print stores correct count', function () {
    printLog_createMappedPeserta($this->event, ['nama' => 'Peserta 2', 'nip' => 8002, 'attendance_code' => 'KJA-P2', 'jenis_kelamin' => 'Laki - Laki']);
    printLog_createMappedPeserta($this->event, ['nama' => 'Peserta 3', 'nip' => 8003, 'attendance_code' => 'KJA-P3', 'jenis_kelamin' => 'Perempuan']);

    $this->get(route('qr-label.print.filtered'));

    $log = ActivityLog::where('module', 'print')
        ->where('action', 'print_viewed')
        ->where('properties->print_type', 'qr_label_filtered')
        ->first();

    expect($log->properties['count'])->toBe(3);
    expect($log->properties['format'])->toBe('4x4_single');
});

test('qr label batch filtered print response remains successful', function () {
    printLog_createMappedPeserta($this->event, ['nama' => 'Peserta 2', 'nip' => 8002, 'attendance_code' => 'KJA-P2', 'jenis_kelamin' => 'Laki - Laki']);

    $this->get(route('qr-label.print.filtered'))
        ->assertStatus(200);
});

// ---------------------------------------------------------------------------
// QR Label batch A4 print — activity log
// ---------------------------------------------------------------------------

test('qr label batch a4 print creates exactly one activity log entry', function () {
    printLog_createMappedPeserta($this->event, ['nama' => 'Peserta 2', 'nip' => 8002, 'attendance_code' => 'KJA-P2', 'jenis_kelamin' => 'Laki - Laki']);
    printLog_createMappedPeserta($this->event, ['nama' => 'Peserta 3', 'nip' => 8003, 'attendance_code' => 'KJA-P3', 'jenis_kelamin' => 'Perempuan']);

    $this->get(route('qr-label.print.a4'));

    $this->assertEquals(1, ActivityLog::where('module', 'print')
        ->where('action', 'print_viewed')
        ->where('properties->print_type', 'qr_label_a4')
        ->count());
});

test('qr label batch a4 print stores correct count and format', function () {
    printLog_createMappedPeserta($this->event, ['nama' => 'Peserta 2', 'nip' => 8002, 'attendance_code' => 'KJA-P2', 'jenis_kelamin' => 'Laki - Laki']);

    $this->get(route('qr-label.print.a4'));

    $log = ActivityLog::where('module', 'print')
        ->where('action', 'print_viewed')
        ->where('properties->print_type', 'qr_label_a4')
        ->first();

    expect($log->properties['count'])->toBe(2);
    expect($log->properties['format'])->toBe('a4_grid');
});

test('qr label batch a4 print response remains successful', function () {
    printLog_createMappedPeserta($this->event, ['nama' => 'Peserta 2', 'nip' => 8002, 'attendance_code' => 'KJA-P2', 'jenis_kelamin' => 'Laki - Laki']);

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
