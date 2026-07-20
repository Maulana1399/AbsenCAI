<?php

namespace App\Livewire\Pengajian;

use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\desa;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth.simple')]
class DesaDashboard extends Component
{
    public ?string $eventName = null;

    public ?string $desaName = null;

    public ?string $validFrom = null;

    public ?string $validUntil = null;

    public bool $processing = false;

    public function mount(): void
    {
        $session = session('pengajian_access');

        if ($session === null || ! isset($session['grant_id'], $session['event_id'], $session['desa_id'])) {
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        $grant = DesaAccessGrant::with('event', 'desa')->find($session['grant_id']);

        if ($grant === null) {
            session()->forget('pengajian_access');
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        if ((int) $grant->event_id !== (int) $session['event_id']) {
            session()->forget('pengajian_access');
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        if ((int) $grant->desa_id !== (int) $session['desa_id']) {
            session()->forget('pengajian_access');
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        if ($grant->revoked_at !== null) {
            session()->forget('pengajian_access');
            session()->flash('pengajian_expired', 'Sesi akses telah dicabut. Silakan hubungi Operator Daerah.');
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        if (now()->greaterThan($grant->valid_until)) {
            session()->forget('pengajian_access');
            session()->flash('pengajian_expired', 'Masa berlaku akses telah habis. Silakan minta token baru.');
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        if (now()->lessThan($grant->valid_from)) {
            session()->forget('pengajian_access');
            session()->flash('pengajian_expired', 'Token belum dapat digunakan. Periksa kembali masa berlaku.');
            $this->redirect(route('pengajian.enter-token', absolute: false), navigate: true);
            return;
        }

        $this->eventName = $grant->event->name;
        $this->desaName = $grant->desa->desa_asal;
        $this->validFrom = $grant->valid_from->format('d M Y H:i');
        $this->validUntil = $grant->valid_until->format('d M Y H:i');
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
}
