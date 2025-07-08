<?php

namespace App\Livewire\Database\Regu;

use App\Models\regu;
use Livewire\Component;
use Livewire\Attributes\On;

class DataRegu extends Component
{

    public $daftarregu;

    public function mount(){
        $this->daftarregu = regu::all();

        // dd($this->daftarregu);
    }

    public function render()    
    {
         return view('livewire.database.regu.data-regu', [
            'daftarregu' => $this->daftarregu
        ]);
    }

    public function edit($id)
    {
        $this->dispatch('editRegu', id: $id);
    }

    public function delete($id)
    {
        $this->dispatch('HapusRegu', id: $id);

    }
    
    #[On('refreshRegu')]
    public function refreshRegu()
    {
        $this->daftarregu = regu::all();
    }
}
