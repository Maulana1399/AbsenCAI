<?php

namespace App\Http\Controllers;

use App\Services\Import\Adapters\ImportAdapter;
use App\Services\Import\Adapters\Peserta\PesertaImportCommitter;
use App\Services\Import\Adapters\Peserta\PesertaImportDefinition;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\DTO\ImportError;
use App\Services\Import\Exceptions\ImportException;
use App\Services\Import\NullObjects\NullImportActivityLogger;
use App\Services\Import\NullObjects\NullImportDuplicateDetector;
use App\Services\Import\NullObjects\NullImportNormalizer;
use App\Services\Import\NullObjects\NullImportParser;
use App\Services\Import\NullObjects\NullImportValidator;
use App\Services\Import\Pipeline\DefaultImportPipeline;
use App\Services\Import\Pipeline\ImportCoordinator;
use App\Services\Import\Registry\ImportRegistry;
use App\Services\Import\Support\ArrayPipelineStageRunner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;
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

        return $this->executeImport(
            $request,
            'peserta',
            new PesertaImportDefinition(
                new NullImportParser,
                new NullImportValidator,
                new NullImportNormalizer,
                new NullImportDuplicateDetector,
                new PesertaImportCommitter,
                new NullImportActivityLogger,
            ),
            'Data peserta berhasil diimpor.',
        );
    }

    private function executeImport(Request $request, string $type, object $definition, string $message): RedirectResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $registry = new ImportRegistry;
        $registry->register($definition);

        $coordinator = new ImportCoordinator(
            $registry,
            new DefaultImportPipeline(new ArrayPipelineStageRunner),
        );

        $context = new ImportContext(
            type: $type,
            userId: $request->user()?->id,
            fileName: $request->file('file')?->getClientOriginalName(),
            source: 'controller',
            mode: 'execute',
            options: ['file' => $request->file('file')],
            definitionKey: $type,
        );

        try {
            $coordinator->execute($type, $context, $request->file('file'));
        } catch (ExcelValidationException $e) {
            $messages = [];

            foreach ($e->failures() as $failure) {
                $attribute = method_exists($failure, 'attribute') ? $failure->attribute() : ($failure['attribute'] ?? 'file');
                $errors = method_exists($failure, 'errors') ? $failure->errors() : ($failure['errors'] ?? []);

                foreach ($errors as $msg) {
                    $messages[$attribute][] = $msg;
                }
            }

            return back()->withErrors($messages);
        }

        return back()->with('success', $message);
    }
}
