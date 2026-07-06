<?php

namespace App\Livewire\Database\Sesi;

use App\Models\SesiAbsensi;
use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;

class EditSesi extends Component
{
    public $sesi;
    public $sesi_id;
    public $nama_sesi = '';
    public $tanggal = '';
    public $aktif = false;

    #[On('editSesi')]
    public function editSesi($id)
    {
        $data = SesiAbsensi::find($id);
        $this->sesi_id = $data->id;
        $this->nama_sesi = $data->nama_sesi;
        $this->tanggal = $data->tanggal;
        $this->aktif = (bool) $data->aktif;
        Flux::modal('edit-sesi')->show();
    }

    public function update()
    {
        $this->validate([
            'nama_sesi' => 'required|string',
            'tanggal' => 'required|date',
        ]);

        if ($this->aktif) {
            SesiAbsensi::query()->update(['aktif' => false]);
        }

        SesiAbsensi::where('id', $this->sesi_id)->update([
            'nama_sesi' => $this->nama_sesi,
            'tanggal' => $this->tanggal,
            'aktif' => $this->aktif ? 1 : 0,
        ]);

        return redirect()->to('/sesi-absensi');
    }

    public function render()
    {
        return view('livewire.database.sesi.edit-sesi');
    }
}
