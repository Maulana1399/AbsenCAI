<?php

namespace App\Http\Controllers;

use App\Services\Import\Adapters\Desa\DesaImportActivityLogger;
use App\Services\Import\Adapters\Desa\DesaImportCommitter;
use App\Services\Import\Adapters\Desa\DesaImportDefinition;
use App\Services\Import\Adapters\Desa\DesaImportDuplicateDetector;
use App\Services\Import\Adapters\Desa\DesaImportNormalizer;
use App\Services\Import\Adapters\Desa\DesaImportParser;
use App\Services\Import\Adapters\Desa\DesaImportValidator;
use App\Services\Import\Adapters\Kelompok\KelompokImportCommitter;
use App\Services\Import\Adapters\Kelompok\KelompokImportDefinition;
use App\Services\Import\Adapters\Peserta\PesertaImportCommitter;
use App\Services\Import\Adapters\Peserta\PesertaImportDefinition;
use App\Services\Import\Adapters\Regu\ReguImportCommitter;
use App\Services\Import\Adapters\Regu\ReguImportDefinition;
use App\Services\Import\DTO\ImportContext;
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
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;

class ImportDataController extends Controller
{
    public function desa(Request $request): RedirectResponse
    {
        Gate::authorize('manage-master-data');

        return $this->executeImport(
            $request,
            'desa',
            new DesaImportDefinition(
                new DesaImportParser,
                new DesaImportValidator,
                new DesaImportNormalizer,
                new DesaImportDuplicateDetector,
                new DesaImportCommitter,
                new DesaImportActivityLogger,
            ),
            'Data desa berhasil diimpor.',
        );
    }

    public function kelompok(Request $request): RedirectResponse
    {
        Gate::authorize('manage-master-data');

        return $this->executeImport(
            $request,
            'kelompok',
            new KelompokImportDefinition(
                new NullImportParser,
                new NullImportValidator,
                new NullImportNormalizer,
                new NullImportDuplicateDetector,
                new KelompokImportCommitter,
                new NullImportActivityLogger,
            ),
            'Data kelompok berhasil diimpor.',
        );
    }

    public function regu(Request $request): RedirectResponse
    {
        Gate::authorize('manage-participants');

        return $this->executeImport(
            $request,
            'regu',
            new ReguImportDefinition(
                new NullImportParser,
                new NullImportValidator,
                new NullImportNormalizer,
                new NullImportDuplicateDetector,
                new ReguImportCommitter,
                new NullImportActivityLogger,
            ),
            'Data regu berhasil diimpor.',
        );
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
