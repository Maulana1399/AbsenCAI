<?php

namespace App\Livewire\Database\Regu;

use App\Models\regu;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class TambahRegu extends Component
{
    public bool $processing = false;
    
    public $regu = '';  
    public $jenis_kelamin = '';  
    
    public function render()
    {
        return view('livewire.database.regu.tambah-regu');
    }
    
    public function simpan(){
        if ($this->processing) {
            return;
        }
        $this->processing = true;

        try {
            $rules = [
                'regu' => 'required|unique:regus,regu',
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
        } finally {
            $this->processing = false;
        }
    }
}
