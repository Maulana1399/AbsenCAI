<?php

namespace App\Livewire\Database\Kelompok;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\kelompok;
use Illuminate\Support\Facades\Gate;
use Flux\Flux;

class HapusKelompok extends Component
{
    public $kelompok_id;
    public $kelompok;


    #[On("HapusKelompok")]
    public function hapusKelompok($id)
    {
        $data = kelompok::find($id);
        $this->kelompok_id = $data->id;
        $this->kelompok = $data->kelompok;
        Flux::modal("hapus-kelompok")->show();
    }
    public function destroy()
    {
        Gate::authorize('manage-master-data');

        $kelompok = kelompok::find($this->kelompok_id);
        if ($kelompok) {
            $kelompok->delete();
            $this->dispatch('refreshKelompok');
            Flux::modal("hapus-kelompok")->close();
        }
    }
    public function render()
    {
        return view('livewire.database.kelompok.hapus-kelompok');
    }
}
