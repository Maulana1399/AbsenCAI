<?php

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\SesiAbsensi;
use App\Models\User;
use App\Models\peserta;
use App\Services\Attendance\AttendanceExceptionService;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\LegacyParticipationResolver;
use App\Services\Attendance\SuratIzinService;
use App\Services\Registration\RegistrationService;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->eventA = Event::create(['name' => 'Caller Bridge A', 'slug' => 'caller-bridge-a', 'status' => 'active']);
    $this->eventB = Event::create(['name' => 'Caller Bridge B', 'slug' => 'caller-bridge-b', 'status' => 'active']);
    $this->desa = \App\Models\desa::create(['desa_asal' => 'Caller Desa']);
    $this->kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Caller Kelompok', 'desa_id' => $this->desa->id]);
    $this->regu = \App\Models\regu::create(['regu' => 'Caller Regu', 'jenis_kelamin' => 'Laki - Laki']);
    $this->user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($this->user);
});

function callerPayload(string $nama, int $nip, $desaId, $kelompokId, $reguId): array
{
    return [
        'nama' => $nama,
        'nip' => $nip,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Wajib',
        'desa_id' => $desaId,
        'kelompok_id' => $kelompokId,
        'regu_id' => $reguId,
        'status_registrasi' => peserta::STATUS_SELF_REGISTER,
    ];
}

test('bridge-first resolver resolves participation by peserta and event', function () {
    $person = Person::create(['nama' => 'Bridge First', 'nip' => 9001, 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);
    $legacy = peserta::create(['nama' => 'Bridge First', 'nip' => 9001, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id, 'status_registrasi' => peserta::STATUS_SELF_REGISTER]);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventA->id, 'participant_number' => 'KL001', 'attendance_code' => 'KJA-A001', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventB->id, 'participant_number' => 'KL002', 'attendance_code' => 'KJA-B001', 'jenis_peserta' => 'Wajib']);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $this->eventA->id]);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partB->id, 'event_id' => $this->eventB->id]);

    $resolver = app(LegacyParticipationResolver::class);
    expect($resolver->resolveByPesertaAndEvent($legacy->id, $this->eventA->id)?->id)->toBe($partA->id)
        ->and($resolver->resolveByPesertaAndEvent($legacy->id, $this->eventB->id)?->id)->toBe($partB->id)
        ->and($resolver->resolvePesertaByParticipation($partB->id, $this->eventB->id)?->id)->toBe($legacy->id);
});

test('attendance scan resolves active event participation and blocks cross-event leakage', function () {
    Config::set('features.attendance_legacy_write', false);

    $person = Person::create(['nama' => 'Scan Bridge', 'nip' => 9002, 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);
    $legacy = peserta::create(['nama' => 'Scan Bridge', 'nip' => 9002, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id, 'status_registrasi' => peserta::STATUS_SELF_REGISTER, 'attendance_code' => 'KJA-SCAN-LEGACY']);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventA->id, 'participant_number' => 'KL010', 'attendance_code' => 'KJA-SCAN-A', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventB->id, 'participant_number' => 'KL011', 'attendance_code' => 'KJA-SCAN-B', 'jenis_peserta' => 'Wajib']);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $this->eventA->id]);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partB->id, 'event_id' => $this->eventB->id]);
    $sesiA = SesiAbsensi::create(['event_id' => $this->eventA->id, 'nama_sesi' => 'Sesi A', 'tanggal' => now()->toDateString(), 'jam_mulai' => '08:00:00', 'jam_selesai' => '09:00:00', 'aktif' => true]);
    $sesiB = SesiAbsensi::create(['event_id' => $this->eventB->id, 'nama_sesi' => 'Sesi B', 'tanggal' => now()->toDateString(), 'jam_mulai' => '08:00:00', 'jam_selesai' => '09:00:00', 'aktif' => true]);

    app(ActiveEventContext::class)->set($this->eventA);
    $resultA = app(AttendanceService::class)->processScan('KJA-SCAN-A', $sesiA->id);
    expect($resultA['status'])->toBe('success');
    expect(\App\Models\EventAttendance::where('participation_id', $partA->id)->where('sesi_absensi_id', $sesiA->id)->exists())->toBeTrue();

    app(ActiveEventContext::class)->set($this->eventB);
    $resultB = app(AttendanceService::class)->processScan('KJA-SCAN-B', $sesiB->id);
    expect($resultB['status'])->toBe('success');
    expect(\App\Models\EventAttendance::where('participation_id', $partB->id)->where('sesi_absensi_id', $sesiB->id)->exists())->toBeTrue();

    app(ActiveEventContext::class)->set($this->eventA);
    expect(app(AttendanceService::class)->processScan('KJA-SCAN-B', $sesiA->id)['status'])->toBe('not_found');
});

test('attendance exception and izin use event-aware participation', function () {
    $person = Person::create(['nama' => 'Izin Bridge', 'nip' => 9003, 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);
    $legacy = peserta::create(['nama' => 'Izin Bridge', 'nip' => 9003, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id, 'status_registrasi' => peserta::STATUS_SELF_REGISTER]);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventA->id, 'participant_number' => 'KL020', 'attendance_code' => 'KJA-IZIN-A', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventB->id, 'participant_number' => 'KL021', 'attendance_code' => 'KJA-IZIN-B', 'jenis_peserta' => 'Wajib']);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $this->eventA->id]);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partB->id, 'event_id' => $this->eventB->id]);
    $sesiA = SesiAbsensi::create(['event_id' => $this->eventA->id, 'nama_sesi' => 'A', 'tanggal' => now()->toDateString(), 'jam_mulai' => '08:00:00', 'jam_selesai' => '09:00:00', 'aktif' => true]);
    $sesiB = SesiAbsensi::create(['event_id' => $this->eventB->id, 'nama_sesi' => 'B', 'tanggal' => now()->toDateString(), 'jam_mulai' => '08:00:00', 'jam_selesai' => '09:00:00', 'aktif' => true]);

    app(AttendanceExceptionService::class)->recordIzin(pesertaId: $legacy->id, sesiId: $sesiA->id, source: 'manual');
    expect(\App\Models\EventAttendance::where('participation_id', $partA->id)->where('sesi_absensi_id', $sesiA->id)->exists())->toBeTrue();

    app(AttendanceExceptionService::class)->recordIzin(pesertaId: $legacy->id, sesiId: $sesiB->id, source: 'manual');
    expect(\App\Models\EventAttendance::where('participation_id', $partB->id)->where('sesi_absensi_id', $sesiB->id)->exists())->toBeTrue();
});

test('surat izin creation resolves participation by active event', function () {
    $person = Person::create(['nama' => 'Surat Bridge', 'nip' => 9004, 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id]);
    $legacy = peserta::create(['nama' => 'Surat Bridge', 'nip' => 9004, 'jenis_kelamin' => 'Laki - Laki', 'desa_id' => $this->desa->id, 'kelompok_id' => $this->kelompok->id, 'status_registrasi' => peserta::STATUS_SELF_REGISTER]);
    $partA = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventA->id, 'participant_number' => 'KL030', 'attendance_code' => 'KJA-SURAT-A', 'jenis_peserta' => 'Wajib']);
    $partB = Participation::create(['person_id' => $person->id, 'event_id' => $this->eventB->id, 'participant_number' => 'KL031', 'attendance_code' => 'KJA-SURAT-B', 'jenis_peserta' => 'Wajib']);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partA->id, 'event_id' => $this->eventA->id]);
    LegacyParticipationMapping::create(['peserta_id' => $legacy->id, 'person_id' => $person->id, 'participation_id' => $partB->id, 'event_id' => $this->eventB->id]);

    app(ActiveEventContext::class)->set($this->eventB);
    $surat = app(SuratIzinService::class)->create([
        'peserta_id' => $legacy->id,
        'alasan' => 'test',
        'tanggal_mulai' => now()->toDateString(),
        'tanggal_selesai' => now()->toDateString(),
    ], $this->user->id);

    expect($surat->participation_id)->toBe($partB->id);
});

test('case b registration remains unchanged and creates new event bridge only', function () {
    $service = app(RegistrationService::class);
    app(ActiveEventContext::class)->set($this->eventA);
    $first = $service->createParticipant(callerPayload('Case B Person', 9010, $this->desa->id, $this->kelompok->id, $this->regu->id));

    app(ActiveEventContext::class)->set($this->eventB);
    $second = $service->createParticipant(callerPayload('Case B Person', 9010, $this->desa->id, $this->kelompok->id, $this->regu->id));

    expect($second->id)->toBe($first->id)
        ->and(Person::count())->toBe(1)
        ->and(peserta::count())->toBe(1)
        ->and(LegacyPesertaMapping::count())->toBe(1)
        ->and(LegacyParticipationMapping::count())->toBe(2)
        ->and(Participation::count())->toBe(2);
});
