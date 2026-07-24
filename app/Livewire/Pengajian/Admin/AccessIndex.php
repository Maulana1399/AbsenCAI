<?php

namespace App\Livewire\Pengajian\Admin;

use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\desa;
use App\Services\Audit\ActivityLogService;
use App\Services\Pengajian\DesaAccessService;
use App\Support\ActiveEventContext;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AccessIndex extends Component
{
    public bool $showCreateForm = false;

    public string $desaId = '';
    public string $validFrom = '';
    public string $validUntil = '';

    public bool $processing = false;

    public ?int $deleteGrantId = null;

    public function render()
    {
        $activeEvent = app(ActiveEventContext::class)->current();

        $grantsQuery = DesaAccessGrant::with('event', 'desa', 'creator')
            ->orderBy('created_at', 'desc');

        if ($activeEvent !== null) {
            $grantsQuery->where('event_id', $activeEvent->id);
        }

        $grants = $grantsQuery->get()->map(fn ($g) => $this->safeGrant($g));

        return view('livewire.pengajian.admin.access-index', [
            'grants' => $grants,
            'activeEvent' => $activeEvent,
            'desas' => desa::orderBy('desa_asal')->get(['id', 'desa_asal']),
        ]);
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;
        if ($this->showCreateForm) {
            $now = now();
            $this->validFrom = $now->format('Y-m-d\TH:i');
            $this->validUntil = $now->addDay()->format('Y-m-d\TH:i');
        }
        $this->resetErrorBag();
    }

    public function create(): void
    {
        Gate::authorize('manage-pengajian');

        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $activeEvent = app(ActiveEventContext::class)->current();

            if ($activeEvent === null) {
                session()->flash('error', 'Tidak ada event aktif.');

                return;
            }

            $this->validate([
                'desaId' => 'required|exists:desas,id',
                'validFrom' => 'required|date',
                'validUntil' => 'required|date|after:validFrom',
            ]);

            $desa = desa::findOrFail($this->desaId);

            $result = app(DesaAccessService::class)->createGrant(
                event: $activeEvent,
                desa: $desa,
                validFrom: Carbon::parse($this->validFrom),
                validUntil: Carbon::parse($this->validUntil),
                createdBy: auth()->user(),
            );

            $this->showCreateForm = false;
            $this->resetForm();
            session()->flash('success', 'Grant akses berhasil dibuat.');

            $this->dispatch('pengajian-raw-token-created',
                token: $result['raw_token'],
                grantId: $result['grant']->id,
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->processing = false;
            throw $e;
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal membuat grant: '.$e->getMessage());
        } finally {
            $this->processing = false;
        }
    }

    public function revoke(int $grantId): void
    {
        Gate::authorize('manage-pengajian');

        $grant = DesaAccessGrant::find($grantId);

        if (! $grant || $grant->isRevoked()) {
            session()->flash('error', 'Grant tidak ditemukan atau sudah dicabut.');

            return;
        }

        if (! $this->grantBelongsToActiveEvent($grant)) {
            session()->flash('error', 'Grant tidak berada dalam event aktif.');

            return;
        }

        app(DesaAccessService::class)->revokeGrant($grant);
        session()->flash('success', 'Grant akses berhasil dicabut.');
    }

    public function confirmDelete(int $grantId): void
    {
        $this->deleteGrantId = $grantId;
    }

    public function cancelDelete(): void
    {
        $this->deleteGrantId = null;
    }

    public function delete(): void
    {
        Gate::authorize('manage-pengajian');

        if ($this->deleteGrantId === null) {
            return;
        }

        $grant = DesaAccessGrant::find($this->deleteGrantId);

        if (! $grant) {
            session()->flash('error', 'Grant tidak ditemukan.');
            $this->deleteGrantId = null;

            return;
        }

        if (! $this->grantBelongsToActiveEvent($grant)) {
            session()->flash('error', 'Grant tidak berada dalam event aktif.');
            $this->deleteGrantId = null;

            return;
        }

        try {
            app(DesaAccessService::class)->deleteGrant($grant);
            $this->deleteGrantId = null;
            session()->flash('success', 'Grant akses berhasil dihapus.');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            $this->deleteGrantId = null;
        }
    }

    public function viewToken(int $grantId): void
    {
        Gate::authorize('manage-pengajian');

        $grant = DesaAccessGrant::find($grantId);

        if (! $grant || ! $this->grantBelongsToActiveEvent($grant)) {
            session()->flash('error', 'Grant tidak ditemukan atau tidak berada dalam event aktif.');

            return;
        }

        if ($grant->encrypted_token === null) {
            $this->dispatch('pengajian-view-token-fallback');

            return;
        }

        try {
            $token = Crypt::decryptString($grant->encrypted_token);

            $this->dispatch('pengajian-view-token', token: $token);

            app(ActivityLogService::class)->log(
                action: 'viewed',
                module: 'pengajian_access',
                description: 'Melihat access token grant #'.$grant->id,
                subject: $grant,
            );
        } catch (DecryptException $e) {
            Log::warning('Gagal mendekripsi encrypted_token di viewToken', [
                'grant_id' => $grant->id,
                'user_id' => auth()->id(),
            ]);

            session()->flash('error', 'Gagal mendekripsi token.');
        }
    }

    private function grantBelongsToActiveEvent(DesaAccessGrant $grant): bool
    {
        $activeEventId = app(ActiveEventContext::class)->id();

        if ($activeEventId === null) {
            return false;
        }

        return (int) $grant->event_id === $activeEventId;
    }

    private function resetForm(): void
    {
        $this->reset(['desaId', 'validFrom', 'validUntil']);
        $this->resetErrorBag();
    }

    private function safeGrant(DesaAccessGrant $g): array
    {
        $status = match (true) {
            $g->isRevoked() => 'revoked',
            $g->isExpired() => 'expired',
            $g->isValid() => 'active',
            default => 'scheduled',
        };

        return [
            'id' => $g->id,
            'event_name' => $g->event?->name,
            'desa_name' => $g->desa?->desa_asal,
            'token_prefix' => $g->token_prefix,
            'valid_from' => $g->valid_from,
            'valid_until' => $g->valid_until,
            'status' => $status,
            'created_by' => $g->creator?->name,
            'created_at' => $g->created_at,
            'revoked_at' => $g->revoked_at,
        ];
    }
}
