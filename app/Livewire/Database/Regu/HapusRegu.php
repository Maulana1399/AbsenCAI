<?php

namespace App\Livewire\Database\Regu;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\regu;
use Flux\Flux;

class HapusRegu extends Component
{
    public $regu_id;
    public $regu;

    #[On("HapusRegu")]
    public function hapusRegu($id)
    {
        // dd($id); // Untuk memastikan method ini dijalankan
        $data = regu::find($id);
        $this->regu_id = $data->id;
        $this->regu = $data->regu;
        Flux::modal("hapus-regu")->show();
    }

    public function destroy()
    {
        $regu = regu::find($this->regu_id);
        if ($regu) {
            $regu->delete();
            $this->dispatch('refreshRegu'); // Tambahkan baris ini
            Flux::modal("hapus-regu")->close(); // Tutup modal jika perlu
        }
    }

    public function render()
    {
        return view('livewire.database.regu.hapus-regu');
    }
}
