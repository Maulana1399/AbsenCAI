<?php

namespace App\Livewire\Database\Regu;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Imports\ReguImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportRegu extends Component
{
    use WithFileUploads;

    public $file;

    public function import()
    {
        $this->validate([
            'file' => 'required|file|mimes:xlsx,csv'
        ]);

        Excel::import(new ReguImport, $this->file->getRealPath());
        session()->flash('success', 'Import berhasil!');
        $this->reset('file');
        $this->dispatch('refreshRegu');
    }

    public function render()
    {
        return view('livewire.database.regu.import-regu');
    }
}
