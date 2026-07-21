<?php

namespace App\Livewire\Database\Desa;

use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\DesaImport;
use Illuminate\Support\Facades\Gate;
use Livewire\WithFileUploads;

class ImportDesa extends Component
{

    use WithFileUploads;

    public $file;

    public function import()
    {
        Gate::authorize('manage-master-data');

        $this->validate([
            'file' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        Excel::import(new DesaImport, $this->file);
        session()->flash('success', 'Data desa berhasil diimpor.');
        $this->reset('file');
        $this->dispatch('refreshDesaList');
    } 
    
    public function render()
    {
        return view('livewire.database.desa.import-desa');
    }
}
