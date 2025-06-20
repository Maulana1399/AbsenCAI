<?php

namespace App\Livewire\Database\Kelompok;

use Livewire\Component;
use App\Imports\KelompokImport;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\WithFileUploads;

class ImportKelompok extends Component
{

    use WithFileUploads;
    public $file;

    public function import()
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,csv,xls',
        ]);

        Excel::import(new KelompokImport, $this->file->getRealPath());
        session()->flash('success', 'Data kelompok berhasil diimpor.');
        $this->reset('file');
        $this->dispatch('refreshKelompokList');
    }

    public function render()
    {
        return view('livewire.database.kelompok.import-kelompok');
    }
}
