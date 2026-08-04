<?php

namespace App\Http\Controllers;

use App\Imports\DesaImport;
use App\Imports\KelompokImport;
use App\Imports\PesertaImport;
use App\Imports\ReguImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;

class ImportDataController extends Controller
{
    public function desa(Request $request): RedirectResponse
    {
        Gate::authorize('manage-master-data');

        return $this->importFile($request, new DesaImport, 'Data desa berhasil diimpor.');
    }

    public function kelompok(Request $request): RedirectResponse
    {
        Gate::authorize('manage-master-data');

        return $this->importFile($request, new KelompokImport, 'Data kelompok berhasil diimpor.');
    }

    public function regu(Request $request): RedirectResponse
    {
        Gate::authorize('manage-participants');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new ReguImport, $request->file('file'));
        } catch (ExcelValidationException $e) {
            $messages = [];

            foreach ($e->failures() as $failure) {
                // Failure is an object with attribute() and errors()
                $attribute = method_exists($failure, 'attribute') ? $failure->attribute() : ($failure['attribute'] ?? 'file');
                $errors = method_exists($failure, 'errors') ? $failure->errors() : ($failure['errors'] ?? []);

                foreach ($errors as $msg) {
                    $messages[$attribute][] = $msg;
                }
            }

            return back()->withErrors($messages);
        }

        return back()->with('success', 'Data regu berhasil diimpor.');
    }

    public function peserta(Request $request): RedirectResponse
    {
        Gate::authorize('manage-participants');

        return $this->importFile($request, new PesertaImport, 'Data peserta berhasil diimpor.');
    }

    private function importFile(Request $request, object $import, string $message): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        Excel::import($import, $request->file('file'));

        return back()->with('success', $message);
    }
}
