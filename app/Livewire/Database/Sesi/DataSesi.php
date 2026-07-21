<?php

namespace App\Livewire\Database\Sesi;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\SesiAbsensi;
use App\Support\ActiveEventContext;

class DataSesi extends Component
{
    public $daftarSesi;

    public function mount()
    {
        $this->loadSesi();
    }

    public function render()
    {
        return view('livewire.database.sesi.data-sesi', [
            'daftarSesi' => $this->daftarSesi,
        ]);
    }

    public function edit($id)
    {
        $this->dispatch('editSesi', id: $id);
    }

    public function delete($id)
    {
        $this->dispatch('HapusSesi', id: $id);
    }

    #[On('refreshSesi')]
    public function refreshSesi()
    {
        $this->loadSesi();
    }

    private function loadSesi(): void
    {
        $eventId = app(ActiveEventContext::class)->id();

        if ($eventId === null) {
            $this->daftarSesi = collect();
            return;
        }

        $this->daftarSesi = SesiAbsensi::where('event_id', $eventId)
            ->orderBy('tanggal', 'desc')
            ->get();
    }
}
