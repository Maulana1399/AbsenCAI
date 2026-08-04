<?php

namespace App\Livewire\MasterData\CorrectionRequest;

use App\Models\desa;
use App\Models\IdentityCorrectionRequest;
use App\Services\Pengajian\IdentityCorrectionService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class IndexCorrectionRequest extends Component
{
    use WithPagination;

    public string $filterStatus = '';
    public string $filterDesaId = '';
    public string $search = '';

    public ?int $reviewingId = null;
    public string $operatorNotes = '';
    public bool $processing = false;

    protected $updatesQueryString = ['filterStatus', 'filterDesaId', 'search'];

    public function render()
    {
        Gate::authorize('view-master-data');

        $query = IdentityCorrectionRequest::with(['person.desa', 'person.kelompok', 'desa', 'event', 'reviewer'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'approved' THEN 1 ELSE 2 END")
            ->orderBy('submitted_at', 'desc');

        if ($this->filterStatus !== '') {
            $query->where('status', $this->filterStatus);
        }

        if ($this->filterDesaId !== '') {
            $query->where('desa_id', $this->filterDesaId);
        }

        if (trim($this->search) !== '') {
            $keyword = trim($this->search);
            $query->whereHas('person', fn ($q) => $q->where('nama', 'like', '%'.$keyword.'%'));
        }

        return view('livewire.master-data.correction-request.index-correction-request', [
            'requests' => $query->paginate(20),
            'desas' => desa::orderBy('desa_asal')->get(),
        ]);
    }

    public function review(int $requestId): void
    {
        $this->reviewingId = $requestId;
        $this->operatorNotes = '';
    }

    public function closeReview(): void
    {
        $this->reviewingId = null;
        $this->operatorNotes = '';
    }

    public function approve(): void
    {
        Gate::authorize('view-master-data');

        if ($this->reviewingId === null || $this->processing) {
            return;
        }

        $this->processing = true;

        try {
            $request = IdentityCorrectionRequest::findOrFail($this->reviewingId);

            app(IdentityCorrectionService::class)->approve(
                $request,
                auth()->user(),
                operatorNotes: $this->operatorNotes ?: null,
            );

            $this->dispatch('correction-processed');
            $this->closeReview();
            session()->flash('success', 'Perubahan data berhasil disetujui.');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal memproses permintaan.');
        } finally {
            $this->processing = false;
        }
    }

    public function reject(): void
    {
        Gate::authorize('view-master-data');

        if ($this->reviewingId === null || $this->processing) {
            return;
        }

        if (trim($this->operatorNotes) === '') {
            $this->dispatch('correction-validation-error', message: 'Catatan operator wajib diisi saat menolak.');
            return;
        }

        $this->processing = true;

        try {
            $request = IdentityCorrectionRequest::findOrFail($this->reviewingId);

            app(IdentityCorrectionService::class)->reject(
                $request,
                auth()->user(),
                operatorNotes: $this->operatorNotes,
            );

            $this->dispatch('correction-processed');
            $this->closeReview();
            session()->flash('success', 'Permintaan perubahan data ditolak.');
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal memproses permintaan.');
        } finally {
            $this->processing = false;
        }
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDesaId(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[On('correction-processed')]
    public function refreshList(): void
    {
        $this->resetPage();
    }
}
