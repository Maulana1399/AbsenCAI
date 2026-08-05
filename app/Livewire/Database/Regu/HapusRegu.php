<?php

namespace App\Livewire\Database\Regu;

use App\Models\regu;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class HapusRegu extends Component
{
    public $regu_id;

    public $regu;

    #[On('HapusRegu')]
    public function hapusRegu($id)
    {
        $data = regu::find($id);
        $this->regu_id = $data->id;
        $this->regu = $data->regu;
        Flux::modal('hapus-regu')->show();
    }

    public function destroy()
    {
        $regu = regu::find($this->regu_id);
        if ($regu) {
            $regu->delete();
            $this->dispatch('refreshRegu');
            Flux::modal('hapus-regu')->close();
        }
    }

    public function render()
    {
        return view('livewire.database.regu.hapus-regu');
    }
}
