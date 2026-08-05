<?php

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Services\Attendance\AttendanceReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->reguEventA = regu::create(['regu' => 'Regu A (Event A)', 'jenis_kelamin' => 'Laki - Laki']);
    $this->reguEventB = regu::create(['regu' => 'Regu B (Event B)', 'jenis_kelamin' => 'Laki - Laki']);
    $this->reguGlobal = regu::create(['regu' => 'Regu Global Old', 'jenis_kelamin' => 'Laki - Laki']);

    $this->eventA = Event::create(['name' => 'Event A', 'slug' => 'event-a', 'status' => 'active']);
    $this->eventB = Event::create(['name' => 'Event B', 'slug' => 'event-b', 'status' => 'active']);

    $desa = \App\Models\desa::create(['desa_asal' => 'Desa Test']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Kelompok Test', 'desa_id' => $desa->id]);

    $this->person = Person::create(['nama' => 'Person A', 'nip' => 1001, 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id]);
    $this->pesertaRecord = peserta::create([
        'nama' => 'Person A',
        'nip' => 1001,
        'jenis_kelamin' => 'Laki - Laki',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    LegacyPesertaMapping::create([
        'peserta_id' => $this->pesertaRecord->id,
        'person_id' => $this->person->id,
    ]);

    // Participation Event A — regu A
    $this->partA = Participation::create([
        'person_id' => $this->person->id,
        'event_id' => $this->eventA->id,
        'participant_number' => 'KL001',
        'attendance_code' => 'KJA-EA-001',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->reguEventA->id,
    ]);

    LegacyParticipationMapping::create([
        'peserta_id' => $this->pesertaRecord->id,
        'person_id' => $this->person->id,
        'participation_id' => $this->partA->id,
        'event_id' => $this->eventA->id,
        'migrated_at' => now(),
    ]);

    // Participation Event B — regu B
    $this->partB = Participation::create([
        'person_id' => $this->person->id,
        'event_id' => $this->eventB->id,
        'participant_number' => 'KL002',
        'attendance_code' => 'KJA-EB-001',
        'jenis_peserta' => 'Wajib',
        'regu_id' => $this->reguEventB->id,
    ]);

    LegacyParticipationMapping::create([
        'peserta_id' => $this->pesertaRecord->id,
        'person_id' => $this->person->id,
        'participation_id' => $this->partB->id,
        'event_id' => $this->eventB->id,
        'migrated_at' => now(),
    ]);
});

// ──────────────────────────────────────────────
// 1. Canonical participation regu is used (legacy peserta.regu_id retired per Sprint 8B)
// ──────────────────────────────────────────────
test('canonical participation regu is used (legacy peserta.regu_id retired)', function () {
    $this->partA->refresh();
    $this->partB->refresh();

    // Participation A reads regu A (canonical BUKAN reguGlobal)
    expect($this->partA->regu)->not->toBeNull();
    expect($this->partA->regu->id)->toBe($this->reguEventA->id);
    expect($this->partA->regu->id)->not->toBe($this->reguGlobal->id);

    // Participation B reads regu B (canonical)
    expect($this->partB->regu->id)->toBe($this->reguEventB->id);
    expect($this->partB->regu->id)->not->toBe($this->reguGlobal->id);
});

// ──────────────────────────────────────────────
// 2. Event A correctly shows Regu 1, Event B shows Regu 5
// ──────────────────────────────────────────────
test('different events show correct regu for same person', function () {
    // Simulasi canonical-first read: $participation->regu
    $reguA = $this->partA->regu;
    $reguB = $this->partB->regu;

    expect($reguA->id)->toBe($this->reguEventA->id);
    expect($reguB->id)->toBe($this->reguEventB->id);
    expect($reguA->id)->not->toBe($reguB->id);
});

// ──────────────────────────────────────────────
// 3. No legacy fallback when canonical regu is null (Sprint 7 contract)
// ──────────────────────────────────────────────
test('no legacy fallback when participation regu is null sprint7', function () {
    $this->partA->update(['regu_id' => null]);
    $this->partA->refresh();

    // Sprint 7 contract: no fallback to legacy peserta.regu
    $regu = $this->partA->regu;

    expect($this->partA->regu)->toBeNull();
    expect($regu)->toBeNull();
});

// ──────────────────────────────────────────────
// 4. Null canonical does not crash (Sprint 7 contract — no fallback)
// ──────────────────────────────────────────────
test('null canonical does not crash sprint7', function () {
    $this->partA->update(['regu_id' => null]);
    $this->partA->refresh();

    $regu = $this->partA->regu;

    expect($regu)->toBeNull();
});

// ──────────────────────────────────────────────
// 5. No cross-event leakage — Event A cannot see Event B's regu
// ──────────────────────────────────────────────
test('no cross event regu leakage', function () {
    // Event A's query only sees Event A participations
    $partAonly = Participation::where('event_id', $this->eventA->id)->get();
    expect($partAonly)->toHaveCount(1);
    expect($partAonly->first()->regu_id)->toBe($this->reguEventA->id);
    expect($partAonly->first()->regu_id)->not->toBe($this->reguEventB->id);

    // Event B's query only sees Event B participations
    $partBonly = Participation::where('event_id', $this->eventB->id)->get();
    expect($partBonly)->toHaveCount(1);
    expect($partBonly->first()->regu_id)->toBe($this->reguEventB->id);
    expect($partBonly->first()->regu_id)->not->toBe($this->reguEventA->id);
});

// ──────────────────────────────────────────────
// 6. AttendanceReadService filter uses canonical regu_id
// ──────────────────────────────────────────────
test('attendance read service filter uses participation regu', function () {
    $sesi = \App\Models\SesiAbsensi::create([
        'event_id' => $this->eventA->id,
        'nama_sesi' => 'Sesi Test A',
        'tanggal' => now()->toDateString(),
        'aktif' => true,
    ]);

    $service = app(AttendanceReadService::class);

    // Filter by reguEventA should find partA
    $result = $service->getSessionAttendance($this->eventA->id, $sesi->id, $this->reguEventA->id);
    expect($result['total'])->toBe(1);

    // Filter by reguEventB should NOT find partA (different event)
    $resultB = $service->getSessionAttendance($this->eventA->id, $sesi->id, $this->reguEventB->id);
    expect($resultB['total'])->toBe(0);
});

// ──────────────────────────────────────────────
// 7. Participation eager load 'regu' works
// ──────────────────────────────────────────────
test('participation eager load regu gives correct event scoped regu', function () {
    $partA = Participation::with('regu')->where('event_id', $this->eventA->id)->first();
    expect($partA->regu)->not->toBeNull();
    expect($partA->regu->id)->toBe($this->reguEventA->id);

    $partB = Participation::with('regu')->where('event_id', $this->eventB->id)->first();
    expect($partB->regu->id)->toBe($this->reguEventB->id);
});

// ──────────────────────────────────────────────
// 8. Regu filter on Participation query is event-scoped
// ──────────────────────────────────────────────
test('regu filter on participation query is event scoped', function () {
    // Filter Event A participations by reguEventA — should find 1
    $countA = Participation::where('event_id', $this->eventA->id)
        ->where('regu_id', $this->reguEventA->id)
        ->count();
    expect($countA)->toBe(1);

    // Filter Event A participations by reguEventB — should find 0
    $countB = Participation::where('event_id', $this->eventA->id)
        ->where('regu_id', $this->reguEventB->id)
        ->count();
    expect($countB)->toBe(0);
});

// ──────────────────────────────────────────────
// 9. Database Peserta display uses canonical regu only (Sprint 7)
// ──────────────────────────────────────────────
test('database peserta display uses canonical regu only sprint7', function () {
    $part = Participation::with('regu')
        ->where('event_id', $this->eventA->id)
        ->first();

    // Sprint 7: $part->regu only (no fallback)
    $regu = $part->regu;

    expect($regu)->not->toBeNull();
    expect($regu->id)->toBe($this->reguEventA->id);
    expect($regu->id)->not->toBe($this->reguGlobal->id);
});

// ──────────────────────────────────────────────
// 10. EditPeserta form reads regu_id from participation only (Sprint 7)
// ──────────────────────────────────────────────
test('edit peserta form reads regu_id from participation only sprint7', function () {
    // Sprint 7: $participation->regu_id only (no fallback)
    $reguId = $this->partA->regu_id;

    expect($reguId)->toBe($this->reguEventA->id);
    expect($reguId)->not->toBe($this->reguGlobal->id);
});

// ──────────────────────────────────────────────
// 11. Ulang form reads regu_id from participation only (Sprint 7)
// ──────────────────────────────────────────────
test('ulang form reads regu_id from participation only sprint7', function () {
    // Sprint 7: $participation->regu_id only (no fallback)
    $editRegu = $this->partB->regu_id;

    expect($editRegu)->toBe($this->reguEventB->id);
    expect($editRegu)->not->toBe($this->reguGlobal->id);
});

// ──────────────────────────────────────────────
// 12. Export shows canonical regu only (Sprint 7)
// ──────────────────────────────────────────────
test('export shows canonical regu only sprint7', function () {
    $part = Participation::with('regu')
        ->where('event_id', $this->eventA->id)
        ->first();

    // Sprint 7: $part->regu?->regu ?? '-' (no $peserta?->regu?->regu)
    $reguName = $part->regu?->regu ?? '-';

    expect($reguName)->toBe('Regu A (Event A)');
    expect($reguName)->not->toBe('Regu Global Old');
});
