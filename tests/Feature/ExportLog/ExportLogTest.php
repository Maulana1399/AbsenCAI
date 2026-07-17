<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\peserta;
use App\Livewire\Rekap\Peserta\RekapPeserta;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

// ---------------------------------------------------------------------------
// Page access
// ---------------------------------------------------------------------------

test('rekap peserta page requires authentication', function () {
    auth()->logout();
    $this->get('/rekap-peserta')->assertRedirect('/login');
});

test('rekap peserta page is accessible by authenticated user', function () {
    $this->get('/rekap-peserta')->assertStatus(200);
});

// ---------------------------------------------------------------------------
// Export — activity log
// ---------------------------------------------------------------------------

test('export peserta creates activity log entry', function () {
    peserta::create([
        'nama'            => 'Peserta Export 1',
        'nip'             => 9001,
        'attendance_code' => 'KJA-EXP-1',
        'jenis_kelamin'   => 'Laki - Laki',
    ]);

    Livewire::test(RekapPeserta::class)
        ->call('exportExcel');

    $this->assertDatabaseHas('activity_logs', [
        'module'  => 'export',
        'action'  => 'exported',
        'user_id' => $this->user->id,
    ]);
});

test('export peserta stores correct module and action', function () {
    peserta::create([
        'nama'            => 'Peserta Export 2',
        'nip'             => 9002,
        'attendance_code' => 'KJA-EXP-2',
        'jenis_kelamin'   => 'Laki - Laki',
    ]);

    Livewire::test(RekapPeserta::class)
        ->call('exportExcel');

    $log = ActivityLog::where('module', 'export')
        ->where('action', 'exported')
        ->latest()
        ->first();

    expect($log->module)->toBe('export');
    expect($log->action)->toBe('exported');
});

test('export peserta stores correct description', function () {
    peserta::create([
        'nama'            => 'Peserta Export 3',
        'nip'             => 9003,
        'attendance_code' => 'KJA-EXP-3',
        'jenis_kelamin'   => 'Laki - Laki',
    ]);

    Livewire::test(RekapPeserta::class)
        ->call('exportExcel');

    $log = ActivityLog::where('module', 'export')
        ->where('action', 'exported')
        ->latest()
        ->first();

    expect($log->description)->toBe('Mengekspor data peserta');
});

test('export peserta stores correct properties', function () {
    peserta::create([
        'nama'            => 'Peserta Export 4',
        'nip'             => 9004,
        'attendance_code' => 'KJA-EXP-4',
        'jenis_kelamin'   => 'Perempuan',
    ]);

    Livewire::test(RekapPeserta::class)
        ->call('exportExcel');

    $log = ActivityLog::where('module', 'export')
        ->where('action', 'exported')
        ->latest()
        ->first();

    expect($log->properties['export_type'])->toBe('peserta');
    expect($log->properties['format'])->toBe('xlsx');
    expect($log->properties['filename'])->toMatch('/^rekap-peserta-\d{14}\.xlsx$/');
    expect($log->properties['filters'])->toBe([]);
});

test('export peserta stores filters in properties when applied', function () {
    peserta::create([
        'nama'            => 'Peserta Export 5',
        'nip'             => 9005,
        'attendance_code' => 'KJA-EXP-5',
        'jenis_kelamin'   => 'Laki - Laki',
    ]);

    Livewire::test(RekapPeserta::class)
        ->set('jenis_kelamin', 'Laki - Laki')
        ->call('exportExcel');

    $log = ActivityLog::where('module', 'export')
        ->where('action', 'exported')
        ->latest()
        ->first();

    expect($log->properties['filters'])->toHaveKey('jenis_kelamin');
    expect($log->properties['filters']['jenis_kelamin'])->toBe('Laki - Laki');
});

test('export peserta creates exactly one activity log entry per call', function () {
    peserta::create([
        'nama'            => 'Peserta Export 6',
        'nip'             => 9006,
        'attendance_code' => 'KJA-EXP-6',
        'jenis_kelamin'   => 'Laki - Laki',
    ]);

    Livewire::test(RekapPeserta::class)
        ->call('exportExcel');

    $count = ActivityLog::where('module', 'export')
        ->where('action', 'exported')
        ->count();

    expect($count)->toBe(1);
});

test('export peserta records authenticated user', function () {
    peserta::create([
        'nama'            => 'Peserta Export 7',
        'nip'             => 9007,
        'attendance_code' => 'KJA-EXP-7',
        'jenis_kelamin'   => 'Laki - Laki',
    ]);

    Livewire::test(RekapPeserta::class)
        ->call('exportExcel');

    $log = ActivityLog::where('module', 'export')
        ->where('action', 'exported')
        ->latest()
        ->first();

    expect($log->user_id)->toBe($this->user->id);
});

test('export peserta returns download response', function () {
    peserta::create([
        'nama'            => 'Peserta Export 8',
        'nip'             => 9008,
        'attendance_code' => 'KJA-EXP-8',
        'jenis_kelamin'   => 'Laki - Laki',
    ]);

    Livewire::test(RekapPeserta::class)
        ->call('exportExcel')
        ->assertOk();
});
