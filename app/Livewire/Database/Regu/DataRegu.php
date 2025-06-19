<?php

namespace App\Livewire\Database\Regu;

use App\Models\regu;
use Livewire\Component;

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
}
