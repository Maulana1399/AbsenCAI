<?php

namespace App\Livewire\Pengajian;

use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\desa;
use App\Services\Pengajian\DesaAccessService;
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
