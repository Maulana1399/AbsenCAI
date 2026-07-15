<?php

namespace App\Livewire\Database\Peserta;

use Livewire\Component;
use App\Models\peserta;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use App\Services\Registration\RegistrationService;

class EditPeserta extends Component
{

    public $peserta;
    public $nama;
    public $nip;
    public $jenis_kelamin;
    public $jenis_peserta;
    public $desa_id;
    public $kelompok_id;
    public $regu_id;
    public $peserta_id;
    public $daftarDesa = [];
    public $daftarKelompok = [];
    public $daftarRegu = [];


    public function mount()
    {
        $this->daftarDesa = desa::all();
        $this->daftarKelompok = kelompok::with('desa')->get();
        $this->daftarRegu = regu::all();
    }

    #[On("editPeserta")]
    public function editPeserta($id)
    {
        $data = peserta::find($id);
        $this->peserta_id = $data->id;
        $this->nama = $data->nama;
        $this->nip = $data->nip;
        $this->jenis_kelamin = $data->jenis_kelamin;
        $this->jenis_peserta = $data->jenis_peserta;
        $this->desa_id = $data->desa_id;
        $this->kelompok_id = $data->kelompok_id;
        $this->regu_id = $data->regu_id;
        Flux::modal("edit-peserta")->show();
    }
    public function update()
    {
        $this->validate([
            'nama' => 'required',
            'jenis_kelamin' => 'required',
            'jenis_peserta' => 'required|in:Wajib,Kiriman,Person',
            'desa_id' => 'required',
            'kelompok_id' => 'required',
            'regu_id' => 'required'
        ]);

        app(RegistrationService::class)->updateParticipant($this->peserta_id, [
            'nama' => $this->nama,
            'jenis_kelamin' => $this->jenis_kelamin,
            'jenis_peserta' => $this->jenis_peserta,
            'desa_id' => $this->desa_id,
            'kelompok_id' => $this->kelompok_id,
            'regu_id' => $this->regu_id,
        ]);

        return redirect()->to('/database');
    }

    public function render()
    {
        return view('livewire.database.peserta.edit-peserta');
    }
}
