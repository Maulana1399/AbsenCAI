<?php

namespace App\Livewire\Database\Desa;

use App\Models\desa;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class HapusDesa extends Component
{
    public $desa_id;

    public $desa;

    #[On('HapusDesa')]
    public function hapusDesa($id)
    {
        $data = desa::find($id);
        $this->desa_id = $data->id;
        $this->desa = $data->desa_asal;
        Flux::modal('hapus-desa')->show();
    }

    public function destroy()
    {
        Gate::authorize('manage-master-data');

        $desa = desa::find($this->desa_id);
        if ($desa) {
            $desa->delete();
            $this->dispatch('refreshDesa');
            Flux::modal('hapus-desa')->close();
        }
    }

    public function render()
    {
        return view('livewire.database.desa.hapus-desa');
    }
}
