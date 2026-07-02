<?php

namespace App\Http\Controllers;

use App\Imports\DesaImport;
use App\Imports\KelompokImport;
use App\Imports\PesertaImport;
use App\Imports\ReguImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ImportDataController extends Controller
{
    public function desa(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new DesaImport, $request->file('file'));

        return back()->with('success', 'Data desa berhasil diimpor.');
    }

    public function kelompok(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new KelompokImport, $request->file('file'));

        return back()->with('success', 'Data kelompok berhasil diimpor.');
    }

    public function regu(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new ReguImport, $request->file('file'));

        return back()->with('success', 'Data regu berhasil diimpor.');
    }

    public function peserta(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new PesertaImport, $request->file('file'));

        return back()->with('success', 'Data peserta berhasil diimpor.');
    }
}
