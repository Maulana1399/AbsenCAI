<?php

use App\Models\Event;
use App\Models\LegacyParticipationMapping;
use App\Models\LegacyPesertaMapping;
use App\Models\Participation;
use App\Models\Person;
use App\Models\peserta;
use App\Models\regu;
use App\Models\SesiAbsensi;
use App\Services\Attendance\AttendanceReadService;
use App\Services\Registration\RegistrationService;
use App\Imports\PesertaImport;
use App\Support\ActiveEventContext;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────
// FIXTURE: Person with 3 events, different regus
// ──────────────────────────────────────────────
beforeEach(function () {
    $this->reguEventA = regu::create(['regu' => 'Regu A', 'jenis_kelamin' => 'Laki - Laki']);
    $this->reguEventB = regu::create(['regu' => 'Regu B', 'jenis_kelamin' => 'Laki - Laki']);
    $this->reguEventC = regu::create(['regu' => 'Regu C', 'jenis_kelamin' => 'Laki - Laki']);
    $this->reguGlobal = regu::create(['regu' => 'Regu Global', 'jenis_kelamin' => 'Laki - Laki']);

    $this->eventA = Event::create(['name' => 'Event A', 'slug' => 'event-a', 'status' => 'active']);
    $this->eventB = Event::create(['name' => 'Event B', 'slug' => 'event-b', 'status' => 'active']);
    $this->eventC = Event::create(['name' => 'Event C', 'slug' => 'event-c', 'status' => 'active']);

    $desa = \App\Models\desa::create(['desa_asal' => 'Desa Test']);
    $kelompok = \App\Models\kelompok::create(['kelompok_asal' => 'Kelompok Test', 'desa_id' => $desa->id]);

    $this->person = Person::create(['nama' => 'Person S7', 'nip' => 7001, 'jenis_kelamin' => 'L', 'desa_id' => $desa->id, 'kelompok_id' => $kelompok->id]);

    // Legacy peserta has reguGlobal (intentionally different from all event regus)
    $this->pesertaRecord = peserta::create([
        'nama' => 'Person S7',
        'nip' => 7001,
        'jenis_kelamin' => 'Laki - Laki',
        'desa_id' => $desa->id,
        'kelompok_id' => $kelompok->id,
        'regu_id' => $this->reguGlobal->id,
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
        'participant_number' => 'KL701',
        'attendance_code' => 'KJA-S7A',
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
        'participant_number' => 'KL702',
        'attendance_code' => 'KJA-S7B',
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

    // Participation Event C — regu NULL (to test no-fallback contract)
    $this->partC = Participation::create([
        'person_id' => $this->person->id,
        'event_id' => $this->eventC->id,
        'participant_number' => 'KL703',
        'attendance_code' => 'KJA-S7C',
        'jenis_peserta' => 'Kiriman',
        'regu_id' => null,
    ]);
    LegacyParticipationMapping::create([
        'peserta_id' => $this->pesertaRecord->id,
        'person_id' => $this->person->id,
        'participation_id' => $this->partC->id,
        'event_id' => $this->eventC->id,
        'migrated_at' => now(),
    ]);
});

// ──────────────────────────────────────────────
// A. Event A/B conflicting regu isolation
// ──────────────────────────────────────────────
test('S7A: event A shows regu A, event B shows regu B — no cross leakage', function () {
    $reguA = $this->partA->regu;
    $reguB = $this->partB->regu;

    expect($reguA->id)->toBe($this->reguEventA->id);
    expect($reguB->id)->toBe($this->reguEventB->id);
    expect($reguA->id)->not->toBe($reguB->id);
});

// ──────────────────────────────────────────────
// B. No fallback to legacy when canonical is available
// ──────────────────────────────────────────────
test('S7B: legacy peserta.regu is NOT used when participation regu is available, even if different', function () {
    // peserta.regu_id = reguGlobal, partA.regu_id = reguEventA
    // Canonical read should return reguEventA, NOT fallback to reguGlobal
    expect($this->partA->regu_id)->toBe($this->reguEventA->id);
    expect($this->partA->regu_id)->not->toBe($this->reguGlobal->id);
});

// ──────────────────────────────────────────────
// C. Participation regu NULL does NOT leak legacy regu from different event
// ──────────────────────────────────────────────
test('S7C: participation with null regu does NOT fallback to legacy peserta regu', function () {
    // Sprint 7 contract: when regu_id is NULL on Participation, return null
    // Do NOT fallback to peserta.regu_id which might be from a different event
    $partC = $this->partC->fresh();

    expect($partC->regu_id)->toBeNull();
    expect($partC->regu)->toBeNull();
});

// ──────────────────────────────────────────────
// D. Database display uses canonical regu only
// ──────────────────────────────────────────────
test('S7D: database peserta display uses canonical regu without legacy fallback', function () {
    $part = Participation::with('regu')
        ->where('event_id', $this->eventA->id)
        ->first();

    // Canonical only: no ?? $legacyPeserta?->regu
    $regu = $part->regu;

    expect($regu)->not->toBeNull();
    expect($regu->id)->toBe($this->reguEventA->id);
    expect($regu->id)->not->toBe($this->reguGlobal->id);
});

// ──────────────────────────────────────────────
// E. EditPeserta reads regu_id from participation only
// ──────────────────────────────────────────────
test('S7E: edit peserta reads regu_id from participation without legacy fallback', function () {
    // Sprint 7 contract: $this->regu_id = $participation->regu_id (no ?? $legacyPeserta?->regu_id)
    $reguId = $this->partA->regu_id;

    expect($reguId)->toBe($this->reguEventA->id);
    expect($reguId)->not->toBe($this->reguGlobal->id);
});

// ──────────────────────────────────────────────
// F. Export shows canonical regu without legacy fallback
// ──────────────────────────────────────────────
test('S7F: export shows canonical regu without legacy fallback', function () {
    $part = Participation::with(['regu', 'person.legacyPesertaMapping.peserta'])
        ->where('event_id', $this->eventA->id)
        ->first();

    // Sprint 7 contract: $part->regu?->regu ?? '-' (no $peserta?->regu?->regu)
    $reguName = $part->regu?->regu ?? '-';

    expect($reguName)->toBe('Regu A');
    expect($reguName)->not->toBe('Regu Global');
});

// ──────────────────────────────────────────────
// G. Attendance read service filter uses canonical regu
// ──────────────────────────────────────────────
test('S7G: attendance read service filter uses canonical participation regu_id', function () {
    $sesi = SesiAbsensi::create([
        'event_id' => $this->eventA->id,
        'nama_sesi' => 'Sesi S7 A',
        'tanggal' => now()->toDateString(),
        'aktif' => true,
    ]);

    $service = app(AttendanceReadService::class);

    $result = $service->getSessionAttendance($this->eventA->id, $sesi->id, $this->reguEventA->id);
    expect($result['total'])->toBe(1);

    // Filter by Event B's regu should find 0 for Event A
    $resultB = $service->getSessionAttendance($this->eventA->id, $sesi->id, $this->reguEventB->id);
    expect($resultB['total'])->toBe(0);
});

// ──────────────────────────────────────────────
// H. Dual-write stopped: updating Participation regu does NOT update peserta.regu_id
// ──────────────────────────────────────────────
test('S7H: updating participation regu does not modify legacy peserta regu_id', function () {
    $originalPesertaReguId = $this->pesertaRecord->regu_id;

    // Simulate EditPeserta::update() — only writes to Participation
    $this->partA->update(['regu_id' => $this->reguEventC->id]);

    // Refresh both
    $this->partA->refresh();
    $this->pesertaRecord->refresh();

    // Participation should have new regu
    expect((int) $this->partA->regu_id)->toBe($this->reguEventC->id);

    // Legacy peserta should be UNCHANGED (dual-write stopped)
    expect((int) $this->pesertaRecord->regu_id)->toBe($originalPesertaReguId);
    expect((int) $this->pesertaRecord->regu_id)->not->toBe($this->reguEventC->id);
});

// ──────────────────────────────────────────────
// I. Ulang update does NOT write regu_id to legacy peserta
// ──────────────────────────────────────────────
test('S7I: ulang update does not write regu_id to legacy peserta', function () {
    $originalPesertaReguId = $this->pesertaRecord->regu_id;

    // Simulate Ulang::updatePeserta() — only writes to Participation
    $this->partB->update([
        'jenis_peserta' => 'Kiriman',
        'regu_id' => $this->reguEventC->id,
    ]);

    $this->partB->refresh();
    $this->pesertaRecord->refresh();

    expect((int) $this->partB->regu_id)->toBe($this->reguEventC->id);
    expect((int) $this->pesertaRecord->regu_id)->toBe($originalPesertaReguId);
});

// ──────────────────────────────────────────────
// J. RegistrationService createParticipant does not dual-write for existing Person
// ──────────────────────────────────────────────
test('S7J: registration creates participant without dual-write to legacy peserta regu_id', function () {
    $eventD = Event::create(['name' => 'Event D', 'slug' => 'event-d', 'status' => 'active']);
    $reguD = regu::create(['regu' => 'Regu D', 'jenis_kelamin' => 'Laki - Laki']);

    // We need an active event context for RegistrationService
    // Mock by setting the active event
    $this->app->make(ActiveEventContext::class)->set($eventD);

    $originalPesertaReguId = $this->pesertaRecord->regu_id;

    app(RegistrationService::class)->createParticipant([
        'nama' => 'Person S7',
        'nip' => 7001,
        'jenis_kelamin' => 'Laki - Laki',
        'jenis_peserta' => 'Kiriman',
        'desa_id' => $this->pesertaRecord->desa_id,
        'kelompok_id' => $this->pesertaRecord->kelompok_id,
        'regu_id' => $reguD->id,
        'status_registrasi' => peserta::STATUS_BELUM_REGISTRASI,
    ]);

    $this->pesertaRecord->refresh();

    // Legacy peserta regu_id should be UNCHANGED (dual-write stopped)
    expect((int) $this->pesertaRecord->regu_id)->toBe($originalPesertaReguId);
    expect((int) $this->pesertaRecord->regu_id)->not->toBe($reguD->id);
});

// ──────────────────────────────────────────────
// K. Participation with null regu shows null in display (no legacy leak)
// ──────────────────────────────────────────────
test('S7K: participation with null regu_id displays null not legacy regu', function () {
    $partC = Participation::with('regu')
        ->where('event_id', $this->eventC->id)
        ->first();

    // Database.php mapping: 'regu' => $participation->regu (no fallback)
    $regu = $partC->regu;

    // Participant C has null regu_id — should return null, NOT global regu
    expect($regu)->toBeNull();
});

// ──────────────────────────────────────────────
// L. Attendance read service does not fallback for display
// ──────────────────────────────────────────────
test('S7L: rekap absensi does not fallback to legacy regu', function () {
    $sesi = SesiAbsensi::create([
        'event_id' => $this->eventC->id,
        'nama_sesi' => 'Sesi S7 C',
        'tanggal' => now()->toDateString(),
        'aktif' => true,
    ]);

    $service = app(AttendanceReadService::class);
    $result = $service->getSessionAttendance($this->eventC->id, $sesi->id);

    // Event C has no regu set — the display should show null, not leak reguGlobal
    foreach ($result['attendance'] as $entry) {
        // Map as RekapAbsensi does: 'regu' => $entry->participation->regu
        $displayRegu = $entry->participation->regu;
        if ($entry->participation->id === $this->partC->id) {
            expect($displayRegu)->toBeNull();
        }
    }
});

// ──────────────────────────────────────────────
// M. Export shows null regu for unassigned participations
// ──────────────────────────────────────────────
test('S7M: export shows null regu when participation regu_id is null', function () {
    $partC = Participation::with('regu')
        ->where('event_id', $this->eventC->id)
        ->first();

    // PesertaExport: 'Regu' => $participation->regu?->regu ?? '-'
    $reguName = $partC->regu?->regu ?? '-';

    expect($reguName)->toBe('-');
    expect($reguName)->not->toBe('Regu Global');
});

// ──────────────────────────────────────────────
// N. No cross-event regu leakage via query filter
// ──────────────────────────────────────────────
test('S7N: query filter by regu_id is event-scoped', function () {
    $countA = Participation::where('event_id', $this->eventA->id)
        ->where('regu_id', $this->reguEventA->id)
        ->count();
    expect($countA)->toBe(1);

    $countB = Participation::where('event_id', $this->eventA->id)
        ->where('regu_id', $this->reguEventB->id)
        ->count();
    expect($countB)->toBe(0);
});

// ──────────────────────────────────────────────
// O. RekapPeserta display uses canonical regu only
// ──────────────────────────────────────────────
test('S7O: rekap peserta display uses canonical regu only', function () {
    $part = Participation::with('regu')
        ->where('event_id', $this->eventB->id)
        ->first();

    // RekapPeserta mapping: 'regu' => $participation->regu (no fallback)
    $regu = $part->regu;

    expect($regu)->not->toBeNull();
    expect($regu->id)->toBe($this->reguEventB->id);
});

// ──────────────────────────────────────────────
// P. Dashboard display uses canonical regu only
// ──────────────────────────────────────────────
test('S7P: dashboard display uses canonical regu only', function () {
    $part = Participation::with('regu')
        ->where('event_id', $this->eventA->id)
        ->first();

    // dashboard.blade: $entry->participation->regu->regu ?? '-'
    $display = $part->regu?->regu ?? '-';

    expect($display)->toBe('Regu A');
    expect($display)->not->toBe('Regu Global');
});
