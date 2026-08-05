<?php

namespace App\Livewire\Pengajian;

use App\Models\DesaAccessGrant;
use App\Services\QR\QRService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.pengajian')]
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
            $this->redirect($this->enterTokenRoute(), navigate: true);

            return;
        }

        $grant = DesaAccessGrant::with('event', 'desa')->find($session['grant_id']);

        if ($grant === null || $grant->revoked_at !== null || now()->greaterThan($grant->valid_until) || now()->lessThan($grant->valid_from)) {
            session()->forget('pengajian_access');
            $this->redirect($this->enterTokenRoute(), navigate: true);

            return;
        }

        if ((int) $grant->event_id !== (int) $session['event_id'] || (int) $grant->desa_id !== (int) $session['desa_id']) {
            session()->forget('pengajian_access');
            $this->redirect($this->enterTokenRoute(), navigate: true);

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

    private function currentEventId(): ?int
    {
        return app(\App\Support\ActiveEventContext::class)->id()
            ?? session('pengajian_access.event_id');
    }

    private function enterTokenRoute(): string
    {
        return route('pengajian.enter-token', ['event' => $this->currentEventId()], absolute: false);
    }

    public function render()
    {
        return view('livewire.pengajian.qr-print');
    }
}
