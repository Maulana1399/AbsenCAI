<?php

namespace App\Livewire\Database\Kelompok;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\kelompok;
use Flux\Flux;

class HapusKelompok extends Component
{
    public $kelompok_id;
    public $kelompok;


    #[On("HapusKelompok")]
    public function hapusKelompok($id)
    {
        // dd($id); // Untuk memastikan method ini dijalankan
        $data = kelompok::find($id);
        $this->kelompok_id = $data->id;
        $this->kelompok = $data->kelompok;
        Flux::modal("hapus-kelompok")->show();
    }
    public function destroy()
    {
        $kelompok = kelompok::find($this->kelompok_id);
        if ($kelompok) {
            $kelompok->delete();
            $this->dispatch('refreshKelompok'); // Tambahkan baris ini
            Flux::modal("hapus-kelompok")->close(); // Tutup modal jika perlu
        }
    }
    public function render()
    {
        return view('livewire..database.kelompok.hapus-kelompok');
    }
}
