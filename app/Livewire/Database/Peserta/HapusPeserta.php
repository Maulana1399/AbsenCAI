<?php

namespace App\Livewire\Database\Peserta;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\peserta;
use Flux\Flux;

class HapusPeserta extends Component
{

    public $peserta_id;
    public $peserta;

    
    #[On("HapusPeserta")]
    public function hapusPeserta($id)
    {
        $data = peserta::find($id);
        $this->peserta_id = $data->id;
        $this->peserta = $data->nama;
        Flux::modal("hapus-peserta")->show();
    }
    public function destroy()
    {
        $peserta = peserta::find($this->peserta_id);
        if ($peserta) {
            $peserta->delete();
            $this->dispatch('refreshPeserta'); // Memanggil event untuk menyegarkan daftar peserta
            Flux::modal("hapus-peserta")->close(); // Menutup modal setelah penghapusan
        }
    }
    
    public function render()
    {
        return view('livewire..database.peserta.hapus-peserta');
    }
}
