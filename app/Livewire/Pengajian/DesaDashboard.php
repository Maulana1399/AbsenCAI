<?php

namespace App\Livewire\Pengajian;

use App\Models\DesaAccessGrant;
use App\Services\Pengajian\DesaAccessService;
use App\Services\Pengajian\PengajianAttendanceService;
use App\Services\Pengajian\PengajianDesaReportService;
use App\Services\Pengajian\PengajianIdentityService;
use App\Services\QR\QRService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.pengajian')]
class DesaDashboard extends Component
{
    public ?string $eventName = null;

    public ?string $desaName = null;

    public ?string $validFrom = null;

    public ?string $validUntil = null;

    public ?string $nonce = null;

    public ?string $qrUrl = null;

    public ?string $qrBase64 = null;

    public bool $processing = false;

    public string $activeTab = 'attendance';

    public string $query = '';

    public array $searchResults = [];

    public bool $searching = false;

    public bool $showingConfirmation = false;

    public ?int $selectedPersonId = null;

    public ?string $selectedPersonName = null;

    public ?string $selectedPersonNumber = null;

    public ?string $selectedPersonKelompok = null;

    public bool $selectedPersonHadir = false;

    public bool $selectedPersonIzin = false;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public array $summary = [];

    public array $attendanceList = [];

    public ?string $filterStatus = null;

    public ?string $filterMethod = null;

    public string $listSearch = '';

    public ?DesaAccessGrant $grant = null;

    public function mount(): void
    {
        $session = session('pengajian_access');

        if ($session === null || ! isset($session['grant_id'], $session['event_id'], $session['desa_id'])) {
            $this->redirect($this->enterTokenRoute(), navigate: true);

            return;
        }

        $grant = DesaAccessGrant::with('event', 'desa')->find($session['grant_id']);

        if ($grant === null) {
            session()->forget('pengajian_access');
            $this->redirect($this->enterTokenRoute(), navigate: true);

            return;
        }

        $grant->makeHidden(['token_hash', 'token_prefix']);
        $this->grant = $grant;

        if ((int) $this->grant->event_id !== (int) $session['event_id']
            || (int) $this->grant->desa_id !== (int) $session['desa_id']) {
            session()->forget('pengajian_access');
            $this->redirect($this->enterTokenRoute(), navigate: true);

            return;
        }

        if ($this->grant->revoked_at !== null) {
            session()->forget('pengajian_access');
            session()->flash('pengajian_expired', 'Sesi akses telah dicabut. Silakan hubungi Operator Daerah.');
            $this->redirect($this->enterTokenRoute(), navigate: true);

            return;
        }

        if (now()->greaterThan($this->grant->valid_until)) {
            session()->forget('pengajian_access');
            session()->flash('pengajian_expired', 'Masa berlaku akses telah habis. Silakan minta token baru.');
            $this->redirect($this->enterTokenRoute(), navigate: true);

            return;
        }

        if (now()->lessThan($this->grant->valid_from)) {
            session()->forget('pengajian_access');
            session()->flash('pengajian_expired', 'Token belum dapat digunakan. Periksa kembali masa berlaku.');
            $this->redirect($this->enterTokenRoute(), navigate: true);

            return;
        }

        $this->eventName = $this->grant->event->name;
        $this->desaName = $this->grant->desa->desa_asal;
        $this->validFrom = $this->grant->valid_from->format('d M Y H:i');
        $this->validUntil = $this->grant->valid_until->format('d M Y H:i');
        $this->nonce = $this->grant->nonce;
        $this->qrUrl = route('pengajian.hadir', ['nonce' => $this->grant->nonce]);
        $this->generateQr();
        $this->loadSummary();
    }

    public function searchPersons(): void
    {
        if ($this->grant === null) {
            return;
        }

        $trimmed = trim($this->query);

        if (mb_strlen($trimmed) < 3) {
            $this->searchResults = [];

            return;
        }

        $this->searching = true;
        $this->errorMessage = null;

        try {
            $this->searchResults = app(PengajianIdentityService::class)
                ->searchPersonsForOperator($this->grant, $trimmed);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Pencarian gagal. Silakan coba lagi.';
            $this->searchResults = [];
        } finally {
            $this->searching = false;
        }
    }

    public function selectPerson(int $personId, ?string $participantNumber = null, ?string $kelompok = null, bool $hadir = false, bool $izin = false): void
    {
        if ($this->grant === null) {
            return;
        }

        $this->errorMessage = null;
        $this->successMessage = null;

        $person = app(PengajianIdentityService::class)
            ->findPersonInDesa($personId, $this->grant->desa_id);

        if ($person === null) {
            $this->errorMessage = 'Peserta tidak valid.';
            $this->selectedPersonId = null;
            $this->selectedPersonName = null;

            return;
        }

        $this->selectedPersonId = $person->id;
        $this->selectedPersonName = $person->nama;
        $this->selectedPersonNumber = $participantNumber;
        $this->selectedPersonKelompok = $kelompok;
        $this->selectedPersonHadir = $hadir;
        $this->selectedPersonIzin = $izin;
        $this->showingConfirmation = true;
    }

    public function confirmOperatorIzin(): void
    {
        if ($this->grant === null || $this->selectedPersonId === null) {
            $this->errorMessage = 'Sesi tidak valid. Silakan refresh halaman.';

            return;
        }

        $this->processing = true;
        $this->errorMessage = null;
        $this->successMessage = null;

        if ($this->selectedPersonHadir) {
            $this->errorMessage = 'Peserta sudah tercatat hadir.';
            $this->processing = false;

            return;
        }

        if ($this->selectedPersonIzin) {
            $this->errorMessage = 'Peserta sudah tercatat izin.';
            $this->processing = false;

            return;
        }

        $person = app(PengajianIdentityService::class)
            ->findPersonInDesa($this->selectedPersonId, $this->grant->desa_id);

        if ($person === null) {
            $this->errorMessage = 'Data peserta tidak valid.';
            $this->processing = false;

            return;
        }

        try {
            app(PengajianAttendanceService::class)->attendPersonOperatorContext(
                $person,
                $this->grant,
                recordedBy: auth()->user(),
                status: \App\Models\EventAttendance::STATUS_IZIN,
            );

            $this->successMessage = 'Izin berhasil dicatat.';
            $this->showingConfirmation = false;
            $this->selectedPersonId = null;
            $this->selectedPersonName = null;
            $this->selectedPersonNumber = null;
            $this->selectedPersonKelompok = null;
            $this->selectedPersonHadir = false;
            $this->query = '';
            $this->searchResults = [];
            $this->loadSummary();
        } catch (\RuntimeException $e) {
            $this->errorMessage = match ($e->getMessage()) {
                'Person tidak memiliki desa assignment.' => 'Peserta belum memiliki desa.',
                'Person tidak terdaftar di desa ini.' => 'Peserta tidak terdaftar di desa ini.',
                'Peserta sudah tercatat hadir.' => 'Peserta sudah tercatat hadir.',
                default => 'Peserta tidak dapat diproses.',
            };
        } catch (\Throwable $e) {
            $this->errorMessage = 'Peserta tidak dapat diproses.';
        } finally {
            $this->processing = false;
        }
    }

    public function confirmOperatorAttendance(): void
    {
        if ($this->grant === null || $this->selectedPersonId === null) {
            $this->errorMessage = 'Sesi tidak valid. Silakan refresh halaman.';

            return;
        }

        $this->processing = true;
        $this->errorMessage = null;
        $this->successMessage = null;

        if ($this->selectedPersonHadir) {
            $this->errorMessage = 'Peserta sudah tercatat hadir.';
            $this->processing = false;

            return;
        }

        if ($this->selectedPersonIzin) {
            $this->errorMessage = 'Peserta sudah tercatat izin.';
            $this->processing = false;

            return;
        }

        $person = app(PengajianIdentityService::class)
            ->findPersonInDesa($this->selectedPersonId, $this->grant->desa_id);

        if ($person === null) {
            $this->errorMessage = 'Data peserta tidak valid.';
            $this->processing = false;

            return;
        }

        try {
            app(PengajianAttendanceService::class)->attendPersonOperatorContext(
                $person,
                $this->grant,
            );

            $this->successMessage = 'Kehadiran berhasil dicatat.';
            $this->showingConfirmation = false;
            $this->selectedPersonId = null;
            $this->selectedPersonName = null;
            $this->selectedPersonNumber = null;
            $this->selectedPersonKelompok = null;
            $this->selectedPersonHadir = false;
            $this->query = '';
            $this->searchResults = [];
            $this->loadSummary();
        } catch (\RuntimeException $e) {
            $this->errorMessage = match ($e->getMessage()) {
                'Person tidak memiliki desa assignment.' => 'Peserta belum memiliki desa. Silakan hubungi operator.',
                'Person tidak terdaftar di desa ini.' => 'Peserta tidak terdaftar di desa ini.',
                'Peserta sudah tercatat hadir.' => 'Peserta sudah tercatat hadir.',
                default => 'Peserta tidak dapat diproses.',
            };
        } catch (\Throwable $e) {
            $this->errorMessage = 'Peserta tidak dapat diproses.';
        } finally {
            $this->processing = false;
        }
    }

    public function resetSelection(): void
    {
        $this->showingConfirmation = false;
        $this->selectedPersonId = null;
        $this->selectedPersonName = null;
        $this->selectedPersonNumber = null;
        $this->selectedPersonKelompok = null;
        $this->selectedPersonHadir = false;
        $this->selectedPersonIzin = false;
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    public function loadSummary(): void
    {
        if ($this->grant === null) {
            return;
        }

        try {
            $this->summary = app(PengajianDesaReportService::class)
                ->summary($this->grant);
        } catch (\Throwable $e) {
            Log::warning('DesaDashboard: summary gagal', [
                'error' => $e->getMessage(),
                'grant_id' => $this->grant->id,
            ]);
            $this->summary = [];
        }
    }

    public function loadAttendanceList(): void
    {
        if ($this->grant === null) {
            return;
        }

        try {
            $this->attendanceList = app(PengajianDesaReportService::class)
                ->attendanceList(
                    $this->grant,
                    search: $this->listSearch ?: null,
                    status: $this->filterStatus ?: null,
                    method: $this->filterMethod ?: null,
                );
        } catch (\Throwable $e) {
            Log::warning('DesaDashboard: attendanceList gagal', [
                'error' => $e->getMessage(),
                'grant_id' => $this->grant->id,
                'event_id' => $this->grant->event_id,
                'desa_id' => $this->grant->desa_id,
            ]);
            $this->attendanceList = [];
        }
    }

    public function updatedActiveTab(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        if ($this->activeTab === 'list') {
            $this->loadAttendanceList();
        }
    }

    public function searchList(string $value): void
    {
        $this->listSearch = $value;
        $this->loadAttendanceList();
    }

    public function setFilterStatus(string $value): void
    {
        $this->filterStatus = $value ?: null;

        if ($this->filterStatus === 'belum') {
            $this->filterMethod = null;
        }

        $this->loadAttendanceList();
    }

    public function setFilterMethod(string $value): void
    {
        $this->filterMethod = $value ?: null;
        $this->loadAttendanceList();
    }

    public function refreshNonce(): void
    {
        if ($this->grant === null) {
            return;
        }

        $grant = $this->grant->fresh();

        if ($grant === null || ! $grant->isValid()) {
            session()->forget('pengajian_access');
            $this->redirect($this->enterTokenRoute(), navigate: true);

            return;
        }

        app(DesaAccessService::class)->rotateNonce($grant);
        $fresh = $grant->fresh();
        $fresh->makeHidden(['token_hash', 'token_prefix']);
        $this->grant = $fresh;
        $this->nonce = $this->grant->nonce;
        $this->qrUrl = route('pengajian.hadir', ['nonce' => $this->grant->nonce]);
        $this->generateQr();
    }

    public function logout(): void
    {
        session()->forget('pengajian_access');
        session()->flash('pengajian_logout', 'Berhasil keluar dari dashboard Pengajian Desa.');
        $this->redirect($this->enterTokenRoute(), navigate: true);
    }

    public function render()
    {
        if ($this->activeTab === 'list' && empty($this->attendanceList)) {
            $this->loadAttendanceList();
        }

        return view('livewire.pengajian.desa-dashboard');
    }

    private function currentEventId(): ?int
    {
        return app(\App\Support\ActiveEventContext::class)->id()
            ?? $this->grant?->event_id
            ?? session('pengajian_access.event_id');
    }

    private function enterTokenRoute(): string
    {
        return route('pengajian.enter-token', ['event' => $this->currentEventId()], absolute: false);
    }

    private function generateQr(): void
    {
        if ($this->qrUrl === null) {
            return;
        }

        try {
            $png = app(QRService::class)->generatePng($this->qrUrl);
            $this->qrBase64 = base64_encode($png);
        } catch (\Throwable) {
            $this->qrBase64 = null;
        }
    }
}
