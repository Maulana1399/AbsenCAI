<?php

namespace App\Livewire\Database\Sesi;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\SesiAbsensi;

class DataSesi extends Component
{
    public $daftarSesi;

    public function mount()
    {
        $this->daftarSesi = SesiAbsensi::orderBy('tanggal','desc')->get();
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
        $this->daftarSesi = SesiAbsensi::orderBy('tanggal','desc')->get();
    }
}
