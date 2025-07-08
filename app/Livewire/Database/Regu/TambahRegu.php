<?php

namespace App\Livewire\Database\Regu;

use Livewire\Component;
use App\Models\regu;

class TambahRegu extends Component
{
    
    public $regu = '';  
    
    public function render()
    {
        return view('livewire.database.regu.tambah-regu');
    }
    
    public function simpan(){
        $this->validate([
            "regu" => "required"
        ]);

        regu::create([
            'regu' => $this->regu,
        ]);
        
        return redirect()->to('/regu');
    }
}
