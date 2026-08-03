<?php

namespace App\Livewire\Pengajian;

use App\Services\Pengajian\DesaAccessService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.pengajian')]
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

        $throttleKey = 'pengajian-token:'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->processing = false;
            $this->addError('token', 'Terlalu banyak percobaan. Silakan coba kembali dalam '.$seconds.' detik.');
            return;
        }

        $grant = app(DesaAccessService::class)->findGrantByToken($this->token);

        if ($grant === null) {
            RateLimiter::hit($throttleKey, 60);
            $this->processing = false;
            $this->addError('token', 'Token tidak valid atau sudah tidak berlaku.');
            return;
        }

        RateLimiter::clear($throttleKey);

        session()->put('pengajian_access', [
            'grant_id' => $grant->id,
            'event_id' => $grant->event_id,
            'desa_id' => $grant->desa_id,
        ]);

        $this->redirect(route('pengajian.desa', ['event' => $grant->event_id], absolute: false), navigate: true);
    }

    public function render()
    {
        return view('livewire.pengajian.enter-token');
    }
}
