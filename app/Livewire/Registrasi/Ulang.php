<?php

namespace App\Livewire\Registrasi;

use App\Models\peserta;
use App\Models\desa;
use App\Models\kelompok;
use App\Models\regu;
use Livewire\Component;

class Ulang extends Component
{
    public string $search = '';

    public $showEditModal = false;

    public $editId;
    public $editNama;
    public $editJenisKelamin;
    public $editJenisPeserta;
    public $editDesa;
    public $editKelompok;
    public $editRegu;

    public function registrasiUlang(int $id): void
    {
        $peserta = peserta::findOrFail($id);

        $peserta->update([
            'status_registrasi' => peserta::STATUS_REGISTRASI_ULANG,
        ]);

        session()->flash('success', 'Registrasi ulang berhasil.');
    }


    public function editPeserta($id)
    {
        $p = peserta::findOrFail($id);

        $this->editId = $p->id;
        $this->editNama = $p->nama;
        $this->editJenisKelamin = $p->jenis_kelamin;
        $this->editJenisPeserta = $p->jenis_peserta;
        $this->editDesa = $p->desa_id;
        $this->editKelompok = $p->kelompok_id;
        $this->editRegu = $p->regu_id;

        $this->showEditModal = true;
    }


    public function updatePeserta()
    {
        peserta::where('id',$this->editId)
            ->update([
                'nama' => $this->editNama,
                'jenis_kelamin' => $this->editJenisKelamin,
                'jenis_peserta' => $this->editJenisPeserta,
                'desa_id' => $this->editDesa,
                'kelompok_id' => $this->editKelompok,
                'regu_id' => $this->editRegu,
            ]);

            $this->showEditModal = false;

            session()->flash('success','Data peserta berhasil diperbarui');

            $this->dispatch('$refresh');
    }


    public function render()
    {
        $search = trim($this->search);

        $peserta = collect();

        if ($search !== '') {
            $peserta = peserta::with(['desa', 'kelompok', 'regu'])
                ->where(function ($query) use ($search) {
                    $query->where('nama', 'like', '%' . $search . '%')
                        ->orWhere('nip', 'like', '%' . $search . '%');
                })
                ->orderBy('nama')
                ->limit(10)
                ->get();
        }

        return view('livewire.registrasi.ulang', [
            'daftarPeserta' => $peserta,
            'daftarDesa' => desa::all(),
            'daftarKelompok' => kelompok::all(),
            'daftarRegu' => regu::all(),
        ]);
    }
}