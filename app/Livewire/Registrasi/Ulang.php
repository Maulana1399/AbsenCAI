<?php

namespace App\Livewire\Registrasi;

use App\Models\peserta;
use Livewire\Component;

class Ulang extends Component
{
    public string $search = '';

    public function registrasiUlang(int $id): void
    {
        $peserta = peserta::findOrFail($id);

        $peserta->update([
            'status_registrasi' => peserta::STATUS_REGISTRASI_ULANG,
        ]);

        session()->flash('success', 'Registrasi ulang berhasil.');
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
        ]);
    }
}
