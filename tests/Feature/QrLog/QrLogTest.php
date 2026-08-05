<?php

use App\Enums\Role;
use App\Livewire\QRLabel\Index as QRLabelIndex;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Services\QR\QRService;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($this->user);

    $event = Event::create([
        'name' => 'QR Log Event',
        'slug' => 'qr-log-event-'.str()->random(6),
        'status' => 'active',
    ]);
    app(ActiveEventContext::class)->set($event);

    $person = Person::create([
        'nama' => 'Peserta QR Log',
        'nip' => 5001,
        'jenis_kelamin' => 'P',
    ]);
    $this->participant = Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'QRLOG001',
        'attendance_code' => 'KJA-QRLOG1',
        'jenis_peserta' => 'Wajib',
    ]);
});

// ---------------------------------------------------------------------------
// downloadPng — activity log
// ---------------------------------------------------------------------------

test('downloadPng requires selected participant', function () {
    Livewire::test(QRLabelIndex::class)
        ->call('downloadPng')
        ->assertOk();

    $this->assertDatabaseHas('activity_logs', [
        'module' => 'qr',
        'action' => 'downloaded',
        'user_id' => $this->user->id,
    ]);
});

test('downloadPng creates activity log entry', function () {
    $fake = new class extends QRService
    {
        public function generatePng(string $attendanceCode): string
        {
            return 'png-binary';
        }
    };
    app()->instance(QRService::class, $fake);

    Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $this->participant->id)
        ->set('selectedParticipant', $this->participant->person)
        ->call('downloadPng');

    $this->assertDatabaseHas('activity_logs', [
        'module' => 'qr',
        'action' => 'downloaded',
        'user_id' => $this->user->id,
        'subject_type' => Person::class,
        'subject_id' => $this->participant->person->id,
    ]);
});

test('downloadPng stores correct description', function () {
    $fake = new class extends QRService
    {
        public function generatePng(string $attendanceCode): string
        {
            return 'png-binary';
        }
    };
    app()->instance(QRService::class, $fake);

    Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $this->participant->id)
        ->set('selectedParticipant', $this->participant->person)
        ->call('downloadPng');

    $log = ActivityLog::where('module', 'qr')
        ->where('action', 'downloaded')
        ->latest()
        ->first();

    expect($log->description)->toBe('Mengunduh QR peserta Peserta QR Log');
});

test('downloadPng stores correct properties', function () {
    $fake = new class extends QRService
    {
        public function generatePng(string $attendanceCode): string
        {
            return 'png-binary';
        }
    };
    app()->instance(QRService::class, $fake);

    Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $this->participant->id)
        ->set('selectedParticipant', $this->participant->person)
        ->call('downloadPng');

    $log = ActivityLog::where('module', 'qr')
        ->where('action', 'downloaded')
        ->latest()
        ->first();

    expect($log->properties)->toMatchArray([
        'qr_type' => 'single',
        'participant_id' => $this->participant->id,
        'participant_number' => 'QRLOG001',
        'attendance_code' => 'KJA-QRLOG1',
        'format' => 'png',
        'filename' => 'QRLOG001.png',
    ]);
});

test('downloadPng records authenticated user', function () {
    $fake = new class extends QRService
    {
        public function generatePng(string $attendanceCode): string
        {
            return 'png-binary';
        }
    };
    app()->instance(QRService::class, $fake);

    Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $this->participant->id)
        ->set('selectedParticipant', $this->participant->person)
        ->call('downloadPng');

    $log = ActivityLog::where('module', 'qr')
        ->where('action', 'downloaded')
        ->latest()
        ->first();

    expect($log->user_id)->toBe($this->user->id);
});

test('downloadPng returns download response', function () {
    $fake = new class extends QRService
    {
        public function generatePng(string $attendanceCode): string
        {
            return 'png-binary';
        }
    };
    app()->instance(QRService::class, $fake);

    Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $this->participant->id)
        ->set('selectedParticipant', $this->participant->person)
        ->call('downloadPng')
        ->assertOk();
});

test('downloadPng creates exactly one log entry per call', function () {
    $fake = new class extends QRService
    {
        public function generatePng(string $attendanceCode): string
        {
            return 'png-binary';
        }
    };
    app()->instance(QRService::class, $fake);

    Livewire::test(QRLabelIndex::class)
        ->set('selectedParticipantId', $this->participant->id)
        ->set('selectedParticipant', $this->participant->person)
        ->call('downloadPng');

    $count = ActivityLog::where('module', 'qr')
        ->where('action', 'downloaded')
        ->where('subject_id', $this->participant->person->id)
        ->count();

    expect($count)->toBe(1);
});

// ---------------------------------------------------------------------------
// generateBatchExport — activity log
// ---------------------------------------------------------------------------

test('generateBatchExport creates activity log entry', function () {
    Storage::fake('local');

    $event = Event::create([
        'name' => 'QR Batch Event',
        'slug' => 'qr-batch-event-'.str()->random(6),
        'status' => 'active',
    ]);
    app(ActiveEventContext::class)->set($event);

    $person = Person::create([
        'nama' => 'Peserta Batch 2',
        'nip' => 5002,
        'jenis_kelamin' => 'P',
    ]);
    Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'QRBAT002',
        'attendance_code' => 'KJA-QRBAT2',
        'jenis_peserta' => 'Wajib',
    ]);

    Livewire::test(QRLabelIndex::class)
        ->call('generateBatchExport');

    $this->assertDatabaseHas('activity_logs', [
        'module' => 'qr',
        'action' => 'batch_exported',
        'user_id' => $this->user->id,
    ]);
});

test('generateBatchExport stores correct module and action', function () {
    Storage::fake('local');

    $event = Event::create([
        'name' => 'QR Batch Event 3',
        'slug' => 'qr-batch-event-3-'.str()->random(6),
        'status' => 'active',
    ]);
    app(ActiveEventContext::class)->set($event);

    $person = Person::create([
        'nama' => 'Peserta Batch 3',
        'nip' => 5003,
        'jenis_kelamin' => 'P',
    ]);
    Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'QRBAT003',
        'attendance_code' => 'KJA-QRBAT3',
        'jenis_peserta' => 'Wajib',
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

    $event = Event::create([
        'name' => 'QR Batch Event 4',
        'slug' => 'qr-batch-event-4-'.str()->random(6),
        'status' => 'active',
    ]);
    app(ActiveEventContext::class)->set($event);

    $person = Person::create([
        'nama' => 'Peserta Batch 4',
        'nip' => 5004,
        'jenis_kelamin' => 'L',
    ]);
    Participation::create([
        'person_id' => $person->id,
        'event_id' => $event->id,
        'participant_number' => 'QRBAT004',
        'attendance_code' => 'KJA-QRBAT4',
        'jenis_peserta' => 'Wajib',
    ]);
    $person2 = Person::create([
        'nama' => 'Peserta Batch 5',
        'nip' => 5005,
        'jenis_kelamin' => 'P',
    ]);
    Participation::create([
        'person_id' => $person2->id,
        'event_id' => $event->id,
        'participant_number' => 'QRBAT005',
        'attendance_code' => 'KJA-QRBAT5',
        'jenis_peserta' => 'Wajib',
    ]);

    Livewire::test(QRLabelIndex::class)
        ->call('generateBatchExport');

    $log = ActivityLog::where('module', 'qr')
        ->where('action', 'batch_exported')
        ->latest()
        ->first();

    expect($log->properties)->toMatchArray([
        'qr_type' => 'batch',
        'format' => 'png',
        'record_count' => 2,
        'skipped' => 0,
        'failed' => 0,
        'directory' => 'qr-exports',
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
