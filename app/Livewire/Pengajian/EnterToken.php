<?php

namespace App\Livewire\Pengajian;

use App\Services\Pengajian\DesaAccessService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth.simple')]
class EnterToken extends Component
{
    public string $token = '';

    public bool $processing = false;

    public function submit(): void
    {
        if ($this->processing) {
            return;
        }

        $this->processing = true;

        $this->validate([
            'token' => ['required', 'string', 'min:16'],
        ]);

        $grant = app(DesaAccessService::class)->findGrantByToken($this->token);

        if ($grant === null) {
            $this->processing = false;
            $this->addError('token', 'Token tidak valid atau sudah tidak berlaku.');
            return;
        }

        session()->put('pengajian_access', [
            'grant_id' => $grant->id,
            'event_id' => $grant->event_id,
            'desa_id' => $grant->desa_id,
        ]);

        $this->redirect(route('pengajian.desa', absolute: false), navigate: true);
    }

    public function render()
    {
        return view('livewire.pengajian.enter-token');
    }
}
