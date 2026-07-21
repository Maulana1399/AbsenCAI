<?php

use App\Livewire\SuratIzin\Create;
use App\Livewire\SuratIzin\Index;
use App\Models\IzinAbsensi;
use App\Models\SesiAbsensi;
use App\Models\SuratIzin;
use App\Models\User;
use App\Models\peserta;
use App\Services\Attendance\SuratIzinService;
use App\Enums\Role;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => Role::Sekretariat]);
    $this->actingAs($this->user);

    $this->peserta = peserta::create([
        'nama'            => 'Peserta Test',
        'nip'             => 9001,
        'attendance_code' => 'KJA-TEST-UI',
        'jenis_kelamin'   => 'Laki - Laki',
    ]);
});

// ---------------------------------------------------------------------------
// Page accessibility
// ---------------------------------------------------------------------------

test('surat izin page is accessible', function () {
    $this->get('/surat-izin')->assertStatus(200);
});

test('surat izin page requires authentication', function () {
    auth()->logout();
    $this->get('/surat-izin')->assertRedirect('/login');
});

// ---------------------------------------------------------------------------
// Create component — validation
// ---------------------------------------------------------------------------

test('create validates peserta required', function () {
    Livewire::test(Create::class)
        ->set('alasan', 'Alasan test panjang')
        ->set('tanggal_mulai', '2026-07-20')
        ->set('tanggal_selesai', '2026-07-21')
        ->call('saveDraft')
        ->assertHasErrors('selectedPesertaId');
});

test('create validates alasan min 5 chars', function () {
    Livewire::test(Create::class)
        ->set('selectedPesertaId', $this->peserta->id)
        ->set('alasan', 'abc')
        ->set('tanggal_mulai', '2026-07-20')
        ->set('tanggal_selesai', '2026-07-21')
        ->call('saveDraft')
        ->assertHasErrors('alasan');
});

test('create validates tanggal_selesai >= tanggal_mulai', function () {
    Livewire::test(Create::class)
        ->set('selectedPesertaId', $this->peserta->id)
        ->set('alasan', 'Alasan yang cukup panjang')
        ->set('tanggal_mulai', '2026-07-21')
        ->set('tanggal_selesai', '2026-07-20')
        ->call('saveDraft')
        ->assertHasErrors('tanggal_selesai');
});

// ---------------------------------------------------------------------------
// Create component — save draft
// ---------------------------------------------------------------------------

test('create saves surat as draft', function () {
    Livewire::test(Create::class)
        ->set('selectedPesertaId', $this->peserta->id)
        ->set('alasan', 'Keperluan keluarga mendesak')
        ->set('tanggal_mulai', '2026-07-20')
        ->set('tanggal_selesai', '2026-07-21')
        ->call('saveDraft');

    $this->assertDatabaseHas('surat_izins', [
        'peserta_id' => $this->peserta->id,
        'status'     => 'draft',
        'alasan'     => 'Keperluan keluarga mendesak',
    ]);
});

// ---------------------------------------------------------------------------
// Create component — save and submit
// ---------------------------------------------------------------------------

test('create saves and submits surat', function () {
    Livewire::test(Create::class)
        ->set('selectedPesertaId', $this->peserta->id)
        ->set('alasan', 'Keperluan keluarga mendesak')
        ->set('tanggal_mulai', '2026-07-20')
        ->set('tanggal_selesai', '2026-07-21')
        ->call('saveAndSubmit');

    $this->assertDatabaseHas('surat_izins', [
        'peserta_id' => $this->peserta->id,
        'status'     => 'pending',
        'alasan'     => 'Keperluan keluarga mendesak',
    ]);
});

// ---------------------------------------------------------------------------
// Create component — jenis izin
// ---------------------------------------------------------------------------

test('create defaults to jenis_izin pulang', function () {
    Livewire::test(Create::class)
        ->set('selectedPesertaId', $this->peserta->id)
        ->set('alasan', 'Keperluan keluarga')
        ->set('tanggal_mulai', '2026-07-20')
        ->set('tanggal_selesai', '2026-07-21')
        ->call('saveDraft');

    $this->assertDatabaseHas('surat_izins', [
        'peserta_id'  => $this->peserta->id,
        'jenis_izin'  => 'pulang',
    ]);
});

test('create saves pulang jenis_izin', function () {
    Livewire::test(Create::class)
        ->set('selectedPesertaId', $this->peserta->id)
        ->set('alasan', 'Keperluan keluarga')
        ->set('jenisIzin', 'pulang')
        ->set('tanggal_mulai', '2026-07-20')
        ->set('tanggal_selesai', '2026-07-21')
        ->call('saveDraft');

    $this->assertDatabaseHas('surat_izins', [
        'peserta_id'  => $this->peserta->id,
        'jenis_izin'  => 'pulang',
    ]);
});

test('create saves keluar jenis_izin', function () {
    Livewire::test(Create::class)
        ->set('selectedPesertaId', $this->peserta->id)
        ->set('alasan', 'Keperluan keluarga')
        ->set('jenisIzin', 'keluar')
        ->set('tanggal_mulai', '2026-07-20')
        ->set('tanggal_selesai', '2026-07-21')
        ->call('saveDraft');

    $this->assertDatabaseHas('surat_izins', [
        'peserta_id'  => $this->peserta->id,
        'jenis_izin'  => 'keluar',
    ]);
});

test('create validates jenis_izin must be pulang or keluar', function () {
    Livewire::test(Create::class)
        ->set('selectedPesertaId', $this->peserta->id)
        ->set('alasan', 'Keperluan keluarga')
        ->set('jenisIzin', 'invalid')
        ->set('tanggal_mulai', '2026-07-20')
        ->set('tanggal_selesai', '2026-07-21')
        ->call('saveDraft')
        ->assertHasErrors('jenisIzin');
});

// ---------------------------------------------------------------------------
// Index — approve action
// ---------------------------------------------------------------------------

test('approve action creates izin absensi for sessions in range', function () {
    $sesi = SesiAbsensi::create([
        'nama_sesi' => 'Sesi Pagi',
        'tanggal'   => '2026-07-20',
        'aktif'     => true,
    ]);

    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ], $this->user->id);

    $surat->update(['status' => 'pending']);

    Livewire::test(Index::class)
        ->call('confirmApprove', $surat->id);

    $this->assertDatabaseHas('surat_izins', [
        'id'     => $surat->id,
        'status' => 'pending',
    ]);

    Livewire::test(Index::class)
        ->set('approveSuratId', $surat->id)
        ->call('approve');

    $this->assertDatabaseHas('surat_izins', [
        'id'     => $surat->id,
        'status' => 'approved',
    ]);

    $this->assertDatabaseHas('izin_absensis', [
        'peserta_id'    => $this->peserta->id,
        'sesi_id'       => $sesi->id,
        'source'        => 'surat_izin',
        'surat_izin_id' => $surat->id,
    ]);
});

// ---------------------------------------------------------------------------
// Index — reject action
// ---------------------------------------------------------------------------

test('reject action sets status to rejected', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-20',
    ], $this->user->id);

    $surat->update(['status' => 'pending']);

    Livewire::test(Index::class)
        ->call('reject', $surat->id);

    $this->assertDatabaseHas('surat_izins', [
        'id'     => $surat->id,
        'status' => 'rejected',
    ]);

    $this->assertDatabaseCount('izin_absensis', 0);
});

// ---------------------------------------------------------------------------
// Index — mark returned action
// ---------------------------------------------------------------------------

test('markReturned sets returned_at on approved surat', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);

    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $this->user);

    Livewire::test(Index::class)
        ->set('returnSuratId', $surat->id)
        ->set('returnDate', '2026-07-20')
        ->call('markReturned');

    $fresh = SuratIzin::find($surat->id);
    expect($fresh->returned_at)->not->toBeNull();
    expect($fresh->returned_at->format('Y-m-d'))->toBe('2026-07-20');
});

test('markReturned removes izin for sessions on and after return date', function () {
    SesiAbsensi::create(['nama_sesi' => 'Sesi 20', 'tanggal' => '2026-07-20', 'aktif' => true]);
    SesiAbsensi::create(['nama_sesi' => 'Sesi 21', 'tanggal' => '2026-07-21', 'aktif' => true]);

    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);

    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $this->user);

    expect(IzinAbsensi::where('surat_izin_id', $surat->id)->count())->toBe(2);

    Livewire::test(Index::class)
        ->set('returnSuratId', $surat->id)
        ->set('returnDate', '2026-07-21')
        ->call('markReturned');

    expect(IzinAbsensi::where('surat_izin_id', $surat->id)->count())->toBe(1);
});

test('markReturned validates return date required', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);

    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $this->user);

    Livewire::test(Index::class)
        ->set('returnSuratId', $surat->id)
        ->set('returnDate', '')
        ->call('markReturned')
        ->assertHasErrors('returnDate');
});

// ---------------------------------------------------------------------------
// List display
// ---------------------------------------------------------------------------

test('index displays surat list', function () {
    app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Keperluan keluarga',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);

    Livewire::test(Index::class)
        ->assertSee('Keperluan keluarga');
});

// ---------------------------------------------------------------------------
// Print
// ---------------------------------------------------------------------------

test('print route requires authentication', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $this->user);

    auth()->logout();

    $this->get(route('surat-izin.print', $surat->id))
        ->assertRedirect('/login');
});

test('print route returns 200 for approved surat', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $this->user);

    $this->get(route('surat-izin.print', $surat->id))
        ->assertStatus(200);
});

test('print route returns 403 for non-approved surat', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Draft',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);

    $this->get(route('surat-izin.print', $surat->id))
        ->assertStatus(403);

    $surat->update(['status' => 'pending']);
    $this->get(route('surat-izin.print', $surat->fresh()->id))
        ->assertStatus(403);
});

test('print output contains surat and participant data', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit demam',
        'jenis_izin'      => 'keluar',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $this->user);

    $response = $this->get(route('surat-izin.print', $surat->id));

    $response->assertStatus(200);
    $response->assertSee($this->peserta->nama);
    $response->assertSee('Sakit demam');
    $response->assertSee('20/07/2026');
    $response->assertSee('21/07/2026');
    $response->assertSee('Keluar');
    $response->assertSee($surat->nomor_surat);
});

test('print does not mutate surat state', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $this->user);

    $originalUpdatedAt = $surat->fresh()->updated_at;

    $this->get(route('surat-izin.print', $surat->id));

    $this->assertEquals(
        $originalUpdatedAt->toDateTimeString(),
        $surat->fresh()->updated_at->toDateTimeString()
    );
});

test('missing logo does not break print', function () {
    $surat = app(SuratIzinService::class)->create([
        'peserta_id'      => $this->peserta->id,
        'alasan'          => 'Sakit',
        'tanggal_mulai'   => '2026-07-20',
        'tanggal_selesai' => '2026-07-21',
    ], $this->user->id);
    $surat->update(['status' => 'pending']);
    app(SuratIzinService::class)->approve($surat->fresh(), $this->user);

    config(['kjam.event_logo' => 'images/nonexistent.png']);
    config(['kjam.org_logo' => 'images/nonexistent.png']);

    $this->get(route('surat-izin.print', $surat->id))
        ->assertStatus(200);
});
