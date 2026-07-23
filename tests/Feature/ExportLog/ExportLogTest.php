<?php

use App\Livewire\Rekap\Peserta\RekapPeserta;
use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\peserta;
use App\Models\regu;
use App\Support\ActiveEventContext;
use App\Enums\Role;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => Role::Admin]);
    $this->actingAs($this->user);
    $this->eventA = Event::create(['name' => 'Event A', 'slug' => 'event-a-'.str()->random(6), 'status' => 'active']);
    $this->eventB = Event::create(['name' => 'Event B', 'slug' => 'event-b-'.str()->random(6), 'status' => 'active']);
    app(ActiveEventContext::class)->set($this->eventA);
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

test('rekap peserta shows only active event participations', function () {
    exportLog_makeMappedParticipation($this->eventA, 'Event A Person', '9101', 'Laki - Laki', 'KL910', 'KJA-A1');
    exportLog_makeMappedParticipation($this->eventB, 'Event B Person', '9201', 'Laki - Laki', 'KL920', 'KJA-B1');

    Livewire::test(RekapPeserta::class)
        ->assertSee('Event A Person')
        ->assertDontSee('Event B Person');
});

test('switching active event changes rekap peserta dataset', function () {
    exportLog_makeMappedParticipation($this->eventA, 'Event A Switch', '9301', 'Laki - Laki', 'KL930', 'KJA-AS1');
    exportLog_makeMappedParticipation($this->eventB, 'Event B Switch', '9401', 'Laki - Laki', 'KL940', 'KJA-BS1');

    Livewire::test(RekapPeserta::class)->assertSee('Event A Switch')->assertDontSee('Event B Switch');

    app(ActiveEventContext::class)->set($this->eventB);

    Livewire::test(RekapPeserta::class)->assertSee('Event B Switch')->assertDontSee('Event A Switch');
});

// ---------------------------------------------------------------------------
// Export — activity log
// ---------------------------------------------------------------------------

function exportLog_makeMappedParticipation(Event $event, string $name, string $nip, string $gender, string $participantNumber, string $attendanceCode, string $status = 'Belum Registrasi'): void
{
    $desa = desa::first() ?? desa::create(['desa_asal' => 'Desa Export']);
    $kelompok = kelompok::first() ?? kelompok::create(['kelompok_asal' => 'Kelompok Export', 'desa_id' => $desa->id]);
    $regu = regu::first() ?? regu::create(['regu' => 'Regu Export', 'jenis_kelamin' => $gender]);
    $peserta = peserta::create(['nama' => $name, 'nip' => (int) $nip, 'participant_number' => $participantNumber, 'attendance_code' => $attendanceCode, 'jenis_kelamin' => $gender === 'Laki - Laki' ? 'Laki - Laki' : 'Perempuan', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id, 'status_registrasi' => $status]);
    $person = Person::create(['nama' => $name, 'nip' => (int) $nip, 'jenis_kelamin' => $gender === 'Laki - Laki' ? 'L' : 'P', 'desa_id' => $desa->id]);
    $participation = Participation::create(['person_id' => $person->id, 'event_id' => $event->id, 'participant_number' => $participantNumber, 'attendance_code' => $attendanceCode, 'jenis_peserta' => peserta::JENIS_WAJIB]);
    LegacyPesertaMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id]);
    LegacyParticipationMapping::create(['peserta_id' => $peserta->id, 'person_id' => $person->id, 'participation_id' => $participation->id, 'event_id' => $event->id]);
}

test('export peserta creates activity log entry', function () {
    exportLog_makeMappedParticipation($this->eventA, 'Peserta Export 1', '9001', 'Laki - Laki', 'KL901', 'KJA-EXP-1');

    Livewire::test(RekapPeserta::class)
        ->call('exportExcel');

    $this->assertDatabaseHas('activity_logs', [
        'module'  => 'export',
        'action'  => 'exported',
        'user_id' => $this->user->id,
    ]);
});

test('export peserta stores correct module and action', function () {
    exportLog_makeMappedParticipation($this->eventA, 'Peserta Export 2', '9002', 'Laki - Laki', 'KL902', 'KJA-EXP-2');

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
    exportLog_makeMappedParticipation($this->eventA, 'Peserta Export 3', '9003', 'Laki - Laki', 'KL903', 'KJA-EXP-3');

    Livewire::test(RekapPeserta::class)
        ->call('exportExcel');

    $log = ActivityLog::where('module', 'export')
        ->where('action', 'exported')
        ->latest()
        ->first();

    expect($log->description)->toBe('Mengekspor data peserta');
});

test('export peserta stores correct properties', function () {
    exportLog_makeMappedParticipation($this->eventA, 'Peserta Export 4', '9004', 'Perempuan', 'KP904', 'KJA-EXP-4');

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
    exportLog_makeMappedParticipation($this->eventA, 'Peserta Export 5', '9005', 'Laki - Laki', 'KL905', 'KJA-EXP-5');
    exportLog_makeMappedParticipation($this->eventB, 'Peserta Export 5 B', '9905', 'Laki - Laki', 'KL915', 'KJA-EXP-5-B');

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
    exportLog_makeMappedParticipation($this->eventA, 'Peserta Export 6', '9006', 'Laki - Laki', 'KL906', 'KJA-EXP-6');

    Livewire::test(RekapPeserta::class)
        ->call('exportExcel');

    $count = ActivityLog::where('module', 'export')
        ->where('action', 'exported')
        ->count();

    expect($count)->toBe(1);
});

test('export peserta records authenticated user', function () {
    exportLog_makeMappedParticipation($this->eventA, 'Peserta Export 7', '9007', 'Laki - Laki', 'KL907', 'KJA-EXP-7');

    Livewire::test(RekapPeserta::class)
        ->call('exportExcel');

    $log = ActivityLog::where('module', 'export')
        ->where('action', 'exported')
        ->latest()
        ->first();

    expect($log->user_id)->toBe($this->user->id);
});

test('export peserta returns download response', function () {
    exportLog_makeMappedParticipation($this->eventA, 'Peserta Export 8', '9008', 'Laki - Laki', 'KL908', 'KJA-EXP-8');

    Livewire::test(RekapPeserta::class)
        ->call('exportExcel')
        ->assertOk();
});
