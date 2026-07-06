<?php

namespace App\Livewire\Database\Sesi;

use App\Models\SesiAbsensi;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

class HapusSesi extends Component
{
    public $sesi_id;
    public $sesi_nama;

    #[On('HapusSesi')]
    public function hapusSesi($id)
    {
        $data = SesiAbsensi::find($id);
        $this->sesi_id = $data->id;
        $this->sesi_nama = $data->nama_sesi;
        Flux::modal('hapus-sesi')->show();
    }

    public function delete()
    {
        SesiAbsensi::where('id', $this->sesi_id)->delete();
        return redirect()->to('/sesi-absensi');
    }

    public function render()
    {
        return view('livewire.database.sesi.hapus-sesi');
    }
}
