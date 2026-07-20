<?php

namespace App\Livewire\Pengajian\Admin;

use App\Models\DesaAccessGrant;
use App\Models\Event;
use App\Models\desa;
use App\Services\Pengajian\DesaAccessService;
use Carbon\Carbon;
use Livewire\Component;

class AccessIndex extends Component
{
    public bool $showCreateForm = false;

    public string $eventId = '';
    public string $desaId = '';
    public string $validFrom = '';
    public string $validUntil = '';

    public bool $processing = false;

    public function render()
    {
        $grants = DesaAccessGrant::with('event', 'desa', 'creator')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($g) => $this->safeGrant($g));

        return view('livewire.pengajian.admin.access-index', [
            'grants' => $grants,
            'events' => Event::orderBy('name')->get(['id', 'name']),
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
        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $this->validate([
                'eventId' => 'required|exists:events,id',
                'desaId' => 'required|exists:desas,id',
                'validFrom' => 'required|date',
                'validUntil' => 'required|date|after:validFrom',
            ]);

            $event = Event::findOrFail($this->eventId);
            $desa = desa::findOrFail($this->desaId);

            $result = app(DesaAccessService::class)->createGrant(
                event: $event,
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
        $grant = DesaAccessGrant::find($grantId);

        if (! $grant || $grant->isRevoked()) {
            session()->flash('error', 'Grant tidak ditemukan atau sudah dicabut.');

            return;
        }

        app(DesaAccessService::class)->revokeGrant($grant);
        session()->flash('success', 'Grant akses berhasil dicabut.');
    }

    private function resetForm(): void
    {
        $this->reset(['eventId', 'desaId', 'validFrom', 'validUntil']);
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
