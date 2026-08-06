<?php

namespace App\Http\Controllers;

use App\Services\Import\Adapters\ImportAdapter;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\Exceptions\ImportException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportDataController extends Controller
{
    public function desa(Request $request): RedirectResponse
    {
        Gate::authorize('manage-master-data');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $result = app(ImportAdapter::class)->commit('desa', $request->file('file'));
        } catch (ImportException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $summary = $result->summary;
        $failed = count($result->commit?->failedRows ?? []);

        return back()->with('success', sprintf(
            'Data desa berhasil diimpor: %d dibuat, %d duplikat dilewati, %d gagal.',
            $summary?->createdRows ?? 0,
            $summary?->skippedRows ?? 0,
            $failed,
        ));
    }

    public function desaTemplate(): BinaryFileResponse
    {
        Gate::authorize('manage-master-data');

        $template = app(ImportAdapter::class)->template('desa');

        return Excel::download($template->toExport(), $template->fileName());
    }

    public function person(Request $request): RedirectResponse
    {
        Gate::authorize('manage-master-data');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $result = app(ImportAdapter::class)->commit('person', $request->file('file'));
        } catch (ImportException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $errors = $result->summary?->errors ?? [];

        if (! empty($errors)) {
            $messages = [];

            foreach ($errors as $error) {
                $field = $error instanceof ImportError ? $error->field : 'file';
                $message = $error instanceof ImportError ? $error->message : ($error['message'] ?? 'Baris tidak valid.');

                $messages[$field] = $message;
            }

            return back()->withErrors($messages);
        }

        $summary = $result->summary;
        $failed = count($result->commit?->failedRows ?? []);

        return back()->with('success', sprintf(
            'Data person berhasil diimpor: %d dibuat, %d duplikat dilewati, %d gagal.',
            $summary?->createdRows ?? 0,
            $summary?->skippedRows ?? 0,
            $failed,
        ));
    }

    public function personTemplate(): BinaryFileResponse
    {
        Gate::authorize('manage-master-data');

        $template = app(ImportAdapter::class)->template('person');

        return Excel::download($template->toExport(), $template->fileName());
    }

    public function participationTemplate(): BinaryFileResponse
    {
        Gate::authorize('manage-registration');

        $template = app(ImportAdapter::class)->template('participation');

        return Excel::download($template->toExport(), $template->fileName());
    }

    public function kelompok(Request $request): RedirectResponse
    {
        Gate::authorize('manage-master-data');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'desa_id' => 'required|integer|exists:desas,id',
        ]);

        try {
            $result = app(ImportAdapter::class)->commit(
                'kelompok',
                $request->file('file'),
                parameters: ['desa_id' => (int) $request->input('desa_id')],
            );
        } catch (ImportException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $summary = $result->summary;
        $failed = count($result->commit?->failedRows ?? []);

        return back()->with('success', sprintf(
            'Data kelompok berhasil diimpor: %d dibuat, %d duplikat dilewati, %d gagal.',
            $summary?->createdRows ?? 0,
            $summary?->skippedRows ?? 0,
            $failed,
        ));
    }

    public function kelompokTemplate(): BinaryFileResponse
    {
        Gate::authorize('manage-master-data');

        $template = app(ImportAdapter::class)->template('kelompok');

        return Excel::download($template->toExport(), $template->fileName());
    }

    public function regu(Request $request): RedirectResponse
    {
        Gate::authorize('manage-participants');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            $result = app(ImportAdapter::class)->commit('regu', $request->file('file'));
        } catch (ImportException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $errors = $result->summary?->errors ?? [];

        if (! empty($errors)) {
            $messages = [];

            foreach ($errors as $error) {
                $field = $error instanceof ImportError ? $error->field : 'file';
                $message = $error instanceof ImportError ? $error->message : ($error['message'] ?? 'Baris tidak valid.');

                $messages[$field] = $message;
            }

            return back()->withErrors($messages);
        }

        $summary = $result->summary;
        $failed = count($result->commit?->failedRows ?? []);

        return back()->with('success', sprintf(
            'Data regu berhasil diimpor: %d dibuat, %d duplikat dilewati, %d gagal.',
            $summary?->createdRows ?? 0,
            $summary?->skippedRows ?? 0,
            $failed,
        ));
    }

    public function reguTemplate(): BinaryFileResponse
    {
        Gate::authorize('manage-participants');

        $template = app(ImportAdapter::class)->template('regu');

        return Excel::download($template->toExport(), $template->fileName());
    }

    public function peserta(Request $request): RedirectResponse
    {
        Gate::authorize('manage-participants');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            app(ImportAdapter::class)->commit('peserta', $request->file('file'));
        } catch (ImportException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        return back()->with('success', 'Data peserta berhasil diimpor.');
    }
}
