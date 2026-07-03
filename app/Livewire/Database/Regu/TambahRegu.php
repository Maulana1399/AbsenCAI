<?php

namespace App\Livewire\Database\Regu;

use App\Models\regu;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class TambahRegu extends Component
{
    
    public $regu = '';  
    public $jenis_kelamin = '';  
    
    public function render()
    {
        return view('livewire.database.regu.tambah-regu');
    }
    
    public function simpan(){
        $rules = [
            'regu' => 'required',
        ];

        $data = [
            'regu' => $this->regu,
        ];

        if (Schema::hasColumn('regus', 'jenis_kelamin')) {
            $rules['jenis_kelamin'] = 'required|in:Laki - Laki,Perempuan';
            $data['jenis_kelamin'] = $this->jenis_kelamin;
        }

        $this->validate($rules);

        regu::create($data);
        
        return redirect()->to('/regu');
    }
}
