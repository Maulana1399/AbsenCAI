<?php

use App\Livewire\QRLabel\Index as QRLabelIndex;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\peserta;
use App\Services\QR\QRService;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->participant = peserta::create([
        'nama'               => 'Peserta QR Log',
        'nip'                => 5001,
        'participant_number' => 'QRLOG001',
        'attendance_code'    => 'KJA-QRLOG1',
        'jenis_kelamin'      => 'Laki - Laki',
    ]);
});

// ---------------------------------------------------------------------------
// downloadPng — activity log
// ---------------------------------------------------------------------------

test('downloadPng requires selected participant', function () {
    Livewire::test(QRLabelIndex::class)
        ->call('downloadPng')
        ->assertStatus(404);

    $this->assertDatabaseMissing('activity_logs', [
        'module' => 'qr',
        'action' => 'downloaded',
    ]);
});

test('downloadPng creates activity log entry', function () {
    $fake = new class extends QRService {
        public function generatePng(string $attendanceCode): string
        {
            return 'png-binary';
        }
    };
    app()->instance(QRService::class, $fake);

    Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $this->participant->id)
        ->set('selectedParticipant', $this->participant)
        ->call('downloadPng');

    $this->assertDatabaseHas('activity_logs', [
        'module'       => 'qr',
        'action'       => 'downloaded',
        'user_id'      => $this->user->id,
        'subject_type' => peserta::class,
        'subject_id'   => $this->participant->id,
    ]);
});

test('downloadPng stores correct description', function () {
    $fake = new class extends QRService {
        public function generatePng(string $attendanceCode): string
        {
            return 'png-binary';
        }
    };
    app()->instance(QRService::class, $fake);

    Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $this->participant->id)
        ->set('selectedParticipant', $this->participant)
        ->call('downloadPng');

    $log = ActivityLog::where('module', 'qr')
        ->where('action', 'downloaded')
        ->latest()
        ->first();

    expect($log->description)->toBe('Mengunduh QR peserta Peserta QR Log');
});

test('downloadPng stores correct properties', function () {
    $fake = new class extends QRService {
        public function generatePng(string $attendanceCode): string
        {
            return 'png-binary';
        }
    };
    app()->instance(QRService::class, $fake);

    Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $this->participant->id)
        ->set('selectedParticipant', $this->participant)
        ->call('downloadPng');

    $log = ActivityLog::where('module', 'qr')
        ->where('action', 'downloaded')
        ->latest()
        ->first();

    expect($log->properties)->toMatchArray([
        'qr_type'            => 'single',
        'peserta_id'         => $this->participant->id,
        'participant_number' => 'QRLOG001',
        'attendance_code'    => 'KJA-QRLOG1',
        'format'             => 'png',
        'filename'           => 'QRLOG001.png',
    ]);
});

test('downloadPng records authenticated user', function () {
    $fake = new class extends QRService {
        public function generatePng(string $attendanceCode): string
        {
            return 'png-binary';
        }
    };
    app()->instance(QRService::class, $fake);

    Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $this->participant->id)
        ->set('selectedParticipant', $this->participant)
        ->call('downloadPng');

    $log = ActivityLog::where('module', 'qr')
        ->where('action', 'downloaded')
        ->latest()
        ->first();

    expect($log->user_id)->toBe($this->user->id);
});

test('downloadPng returns download response', function () {
    $fake = new class extends QRService {
        public function generatePng(string $attendanceCode): string
        {
            return 'png-binary';
        }
    };
    app()->instance(QRService::class, $fake);

    Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $this->participant->id)
        ->set('selectedParticipant', $this->participant)
        ->call('downloadPng')
        ->assertOk();
});

test('downloadPng creates exactly one log entry per call', function () {
    $fake = new class extends QRService {
        public function generatePng(string $attendanceCode): string
        {
            return 'png-binary';
        }
    };
    app()->instance(QRService::class, $fake);

    Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $this->participant->id)
        ->set('selectedParticipant', $this->participant)
        ->call('downloadPng');

    $count = ActivityLog::where('module', 'qr')
        ->where('action', 'downloaded')
        ->where('subject_id', $this->participant->id)
        ->count();

    expect($count)->toBe(1);
});

// ---------------------------------------------------------------------------
// generateBatchExport — activity log
// ---------------------------------------------------------------------------

test('generateBatchExport creates activity log entry', function () {
    Storage::fake('local');

    peserta::create([
        'nama'               => 'Peserta Batch 2',
        'nip'                => 5002,
        'participant_number' => 'QRBAT002',
        'attendance_code'    => 'KJA-QRBAT2',
        'jenis_kelamin'      => 'Perempuan',
    ]);

    Livewire::test(QRLabelIndex::class)
        ->call('generateBatchExport');

    $this->assertDatabaseHas('activity_logs', [
        'module'  => 'qr',
        'action'  => 'batch_exported',
        'user_id' => $this->user->id,
    ]);
});

test('generateBatchExport stores correct module and action', function () {
    Storage::fake('local');

    peserta::create([
        'nama'               => 'Peserta Batch 3',
        'nip'                => 5003,
        'participant_number' => 'QRBAT003',
        'attendance_code'    => 'KJA-QRBAT3',
        'jenis_kelamin'      => 'Perempuan',
    ]);

    Livewire::test(QRLabelIndex::class)
        ->call('generateBatchExport');

    $log = ActivityLog::where('module', 'qr')
        ->where('action', 'batch_exported')
        ->latest()
        ->first();

    expect($log->module)->toBe('qr');
    expect($log->action)->toBe('batch_exported');
});

test('generateBatchExport stores correct description', function () {
    Storage::fake('local');

    Livewire::test(QRLabelIndex::class)
        ->call('generateBatchExport');

    $log = ActivityLog::where('module', 'qr')
        ->where('action', 'batch_exported')
        ->latest()
        ->first();

    expect($log->description)->toBe('Membuat batch QR peserta');
});

test('generateBatchExport stores correct properties', function () {
    Storage::fake('local');

    peserta::create([
        'nama'               => 'Peserta Batch 4',
        'nip'                => 5004,
        'participant_number' => 'QRBAT004',
        'attendance_code'    => 'KJA-QRBAT4',
        'jenis_kelamin'      => 'Laki - Laki',
    ]);

    Livewire::test(QRLabelIndex::class)
        ->call('generateBatchExport');

    $log = ActivityLog::where('module', 'qr')
        ->where('action', 'batch_exported')
        ->latest()
        ->first();

    expect($log->properties)->toMatchArray([
        'qr_type'      => 'batch',
        'format'       => 'png',
        'record_count' => 2,
        'skipped'      => 0,
        'failed'       => 0,
        'directory'    => 'qr-exports',
    ]);
});

test('generateBatchExport creates exactly one log entry per call', function () {
    Storage::fake('local');

    Livewire::test(QRLabelIndex::class)
        ->call('generateBatchExport');

    $count = ActivityLog::where('module', 'qr')
        ->where('action', 'batch_exported')
        ->count();

    expect($count)->toBe(1);
});

test('generateBatchExport records authenticated user', function () {
    Storage::fake('local');

    Livewire::test(QRLabelIndex::class)
        ->call('generateBatchExport');

    $log = ActivityLog::where('module', 'qr')
        ->where('action', 'batch_exported')
        ->latest()
        ->first();

    expect($log->user_id)->toBe($this->user->id);
});

// ---------------------------------------------------------------------------
// No duplicate logs from low-level QRService calls
// ---------------------------------------------------------------------------

test('QRService generatePng does not create activity logs', function () {
    $service = app(QRService::class);

    $service->generatePng('test-code');

    $this->assertDatabaseMissing('activity_logs', [
        'module' => 'qr',
    ]);
});
