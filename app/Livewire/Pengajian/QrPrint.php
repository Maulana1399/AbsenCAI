<?php

namespace App\Livewire\Pengajian;

use App\Models\DesaAccessGrant;
use App\Services\QR\QRService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth.simple')]
class QrPrint extends Component
{
    public ?string $eventName = null;
    public ?string $desaName = null;
    public ?string $qrBase64 = null;
    public ?string $qrUrl = null;

    public function mount(): void
    {
        $session = session('pengajian_access');

        if ($session === null || ! isset($session['grant_id'], $session['event_id'], $session['desa_id'])) {
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        $grant = DesaAccessGrant::with('event', 'desa')->find($session['grant_id']);

        if ($grant === null || $grant->isRevoked() || ! $grant->isValid()) {
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        $this->eventName = $grant->event->name;
        $this->desaName = $grant->desa->desa_asal;
        $this->qrUrl = route('pengajian.hadir', ['nonce' => $grant->nonce]);

        try {
            $png = app(QRService::class)->generatePng($this->qrUrl);
            $this->qrBase64 = base64_encode($png);
        } catch (\Throwable) {
            $this->qrBase64 = null;
        }
    }

    public function render()
    {
        return view('livewire.pengajian.qr-print');
    }
}
