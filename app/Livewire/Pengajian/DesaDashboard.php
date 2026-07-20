<?php

namespace App\Livewire\Pengajian;

use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\desa;
use App\Services\Pengajian\DesaAccessService;
use App\Services\Pengajian\PengajianAttendanceService;
use App\Services\Pengajian\PengajianDesaReportService;
use App\Services\Pengajian\PengajianIdentityService;
use App\Services\QR\QRService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth.simple')]
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

    public ?int $selectedPersonId = null;
    public ?string $selectedPersonName = null;

    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public array $summary = [];

    public array $attendanceList = [];
    public ?string $filterStatus = null;
    public ?string $filterMethod = null;
    public string $listSearch = '';

    private ?DesaAccessGrant $grant = null;

    public function mount(): void
    {
        $session = session('pengajian_access');

        if ($session === null || ! isset($session['grant_id'], $session['event_id'], $session['desa_id'])) {
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        $this->grant = DesaAccessGrant::with('event', 'desa')->find($session['grant_id']);

        if ($this->grant === null) {
            session()->forget('pengajian_access');
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        if ((int) $this->grant->event_id !== (int) $session['event_id']
            || (int) $this->grant->desa_id !== (int) $session['desa_id']) {
            session()->forget('pengajian_access');
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        if ($this->grant->revoked_at !== null) {
            session()->forget('pengajian_access');
            session()->flash('pengajian_expired', 'Sesi akses telah dicabut. Silakan hubungi Operator Daerah.');
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        if (now()->greaterThan($this->grant->valid_until)) {
            session()->forget('pengajian_access');
            session()->flash('pengajian_expired', 'Masa berlaku akses telah habis. Silakan minta token baru.');
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        if (now()->lessThan($this->grant->valid_from)) {
            session()->forget('pengajian_access');
            session()->flash('pengajian_expired', 'Token belum dapat digunakan. Periksa kembali masa berlaku.');
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        $this->eventName = $this->grant->event->name;
        $this->desaName = $this->grant->desa->desa_asal;
        $this->validFrom = $this->grant->valid_from->format('d M Y H:i');
        $this->validUntil = $this->grant->valid_until->format('d M Y H:i');
        $this->nonce = $this->grant->nonce;
        $this->qrUrl = route('pengajian.hadir', ['nonce' => $this->grant->nonce], absolute: false);
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
                ->searchPersons($this->grant, $trimmed);
        } catch (\Throwable $e) {
            $this->errorMessage = 'Pencarian gagal. Silakan coba lagi.';
            $this->searchResults = [];
        } finally {
            $this->searching = false;
        }
    }

    public function selectPerson(int $personId): void
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
            $this->selectedPersonId = null;
            $this->selectedPersonName = null;
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
        $this->selectedPersonId = null;
        $this->selectedPersonName = null;
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
        } catch (\Throwable) {
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
        } catch (\Throwable) {
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

    public function updatedListSearch(): void
    {
        $this->loadAttendanceList();
    }

    public function updatedFilterStatus(): void
    {
        $this->loadAttendanceList();
    }

    public function updatedFilterMethod(): void
    {
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
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        app(DesaAccessService::class)->rotateNonce($grant);
        $this->grant = $grant->fresh();
        $this->nonce = $this->grant->nonce;
        $this->qrUrl = route('pengajian.hadir', ['nonce' => $this->grant->nonce], absolute: false);
        $this->generateQr();
    }

    public function logout(): void
    {
        session()->forget('pengajian_access');
        session()->flash('pengajian_logout', 'Berhasil keluar dari dashboard Pengajian Desa.');
        $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
    }

    public function render()
    {
        if ($this->activeTab === 'list' && empty($this->attendanceList)) {
            $this->loadAttendanceList();
        }

        return view('livewire.pengajian.desa-dashboard');
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
