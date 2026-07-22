<?php

namespace App\Livewire\SuratIzin;

use App\Models\SuratIzin;
use App\Services\Attendance\SuratIzinService;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    public $search = '';
    public $filterStatus = '';

    public $approveSuratId = null;
    public $approveResult = null;

    public $returnSuratId = null;
    public $returnDate = '';

    protected $listeners = ['suratIzinSaved' => '$refresh'];

    public function render()
    {
        $query = SuratIzin::with(['peserta', 'participation.person']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nomor_surat', 'like', '%' . $this->search . '%')
                  ->orWhereHas('peserta', function ($pq) {
                      $pq->where('nama', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('participation.person', function ($pq) {
                      $pq->where('nama', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        $suratList = $query->orderBy('created_at', 'desc')->get();

        return view('livewire.surat-izin.index', compact('suratList'));
    }

    public function submit(int $id)
    {
        Gate::authorize('manage-secretariat');

        $surat = SuratIzin::findOrFail($id);
        try {
            app(SuratIzinService::class)->submit($surat);
            session()->flash('success', 'Surat izin berhasil disubmit.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function confirmApprove(int $id)
    {
        $this->approveSuratId = $id;
        $this->approveResult = null;
        Flux::modal('approve-surat-izin')->show();
    }

    public function approve()
    {
        Gate::authorize('manage-secretariat');

        $surat = SuratIzin::findOrFail($this->approveSuratId);
        try {
            $this->approveResult = app(SuratIzinService::class)->approve($surat, auth()->user());
            session()->flash('success', 'Surat izin berhasil disetujui.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->approveResult = ['error' => $e->getMessage()];
        }
    }

    public function reject(int $id)
    {
        Gate::authorize('manage-secretariat');

        $surat = SuratIzin::findOrFail($id);
        try {
            app(SuratIzinService::class)->reject($surat);
            session()->flash('success', 'Surat izin ditolak.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function confirmReturn(int $id)
    {
        $surat = SuratIzin::findOrFail($id);
        $this->returnSuratId = $surat->id;
        $this->returnDate = today()->toDateString();
        Flux::modal('return-surat-izin')->show();
    }

    public function markReturned()
    {
        Gate::authorize('manage-secretariat');

        $this->validate([
            'returnDate' => 'required|date',
        ]);

        $surat = SuratIzin::findOrFail($this->returnSuratId);
        try {
            app(SuratIzinService::class)->markReturned($surat, $this->returnDate);
            session()->flash('success', 'Peserta ditandai kembali.');
            $this->returnSuratId = null;
            $this->returnDate = '';
            Flux::modal('return-surat-izin')->close();
        } catch (\Illuminate\Validation\ValidationException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function closeApproveModal()
    {
        $this->approveSuratId = null;
        $this->approveResult = null;
    }

    public function closeReturnModal()
    {
        $this->returnSuratId = null;
        $this->returnDate = '';
    }
}
