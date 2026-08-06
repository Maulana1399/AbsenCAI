<?php

namespace App\Services\Import\Adapters\Peserta;

use App\Services\Import\Contracts\ImportActivityLogger;
use App\Services\Import\Contracts\ImportCommitter;
use App\Services\Import\Contracts\ImportDefinition;
use App\Services\Import\Contracts\ImportDefinitionMetadata;
use App\Services\Import\Contracts\ImportDuplicateDetector;
use App\Services\Import\Contracts\ImportNormalizer;
use App\Services\Import\Contracts\ImportParser;
use App\Services\Import\Contracts\ImportTemplate;
use App\Services\Import\Contracts\ImportValidator;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportCommit;
use App\Services\Import\Results\ImportPreview;
use App\Services\Import\Results\ImportSummary;
use App\Services\Import\Template\PesertaImportTemplateExport;

/**
 * Peserta import definition (IF-09). Final legacy migration: real collaborators,
 * no Maatwebsite import, no Excel::import. Event comes from ActiveEventContext
 * (CAI operational), no wizard parameter.
 */
final class PesertaImportDefinition implements ImportDefinition, ImportDefinitionMetadata
{
    public function __construct(
        private readonly ImportParser $parser,
        private readonly ImportValidator $validator,
        private readonly ImportNormalizer $normalizer,
        private readonly ImportDuplicateDetector $duplicateDetector,
        private readonly ImportCommitter $committer,
        private readonly ImportActivityLogger $activityLogger,
    ) {}

    // --- ImportDefinition contract (consumed by the pipeline) ---

    public function key(): string
    {
        return 'peserta';
    }

    public function label(): string
    {
        return 'Import Peserta';
    }

    public function parser(): ImportParser
    {
        return $this->parser;
    }

    public function validator(): ImportValidator
    {
        return $this->validator;
    }

    public function normalizer(): ImportNormalizer
    {
        return $this->normalizer;
    }

    public function duplicateDetector(): ImportDuplicateDetector
    {
        return $this->duplicateDetector;
    }

    public function committer(): ImportCommitter
    {
        return $this->committer;
    }

    public function activityLogger(): ImportActivityLogger
    {
        return $this->activityLogger;
    }

    public function supportedVersion(): string
    {
        return '1.0.0';
    }

    public function minimumVersion(): string
    {
        return '1.0.0';
    }

    public function currentVersion(): string
    {
        return '1.0.0';
    }

    public function supportsPreview(ImportContext $context): bool
    {
        return true;
    }

    public function supportsCommit(ImportContext $context): bool
    {
        return true;
    }

    // --- Module capability API (used by the adapter / wizard) ---

    public function displayName(): string
    {
        return 'Import Peserta';
    }

    public function description(): string
    {
        return 'Import data peserta dari file CSV atau Excel. Desa, kelompok, dan regu diisi otomatis.';
    }

    public function icon(): string
    {
        return 'users';
    }

    public function parameters(): array
    {
        return [];
    }

    public function parameterOptions(string $key, ImportContext $context): array
    {
        return [];
    }

    public function columns(): array
    {
        return [
            'nama' => ['label' => 'Nama', 'required' => true, 'example' => 'Ahmad Wijaya'],
            'jenis_kelamin' => ['label' => 'Jenis Kelamin', 'required' => true, 'example' => 'Laki - Laki'],
            'kelompok' => ['label' => 'Kelompok', 'required' => false, 'example' => 'Kelompok A'],
            'desa' => ['label' => 'Desa', 'required' => false, 'example' => 'Desa Contoh'],
            'jenis_peserta' => ['label' => 'Jenis Peserta', 'required' => false, 'example' => 'Kiriman'],
        ];
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string',
            'jenis_kelamin' => 'required|string',
        ];
    }

    /**
     * @param  array<int, \App\Services\Import\DTO\RawImportRow>  $rows
     * @return array<int, \App\Services\Import\DTO\NormalizedImportRow>
     */
    public function normalize(array $rows, ImportContext $context): array
    {
        return $this->normalizer->normalize($rows, $context);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    public function validateRow(array $row, ImportContext $context): array
    {
        $errors = [];

        if (empty(trim($row['nama'] ?? ''))) {
            $errors[] = 'Nama wajib diisi.';
        }

        if (empty(trim($row['jenis_kelamin'] ?? ''))) {
            $errors[] = 'Jenis kelamin wajib diisi.';
        }

        return $errors;
    }

    /**
     * @param  array<int, \App\Services\Import\DTO\NormalizedImportRow>  $rows
     */
    public function duplicate(array $rows, ImportContext $context): ImportSummary
    {
        return $this->duplicateDetector->detect($rows, $context);
    }

    /**
     * @param  array<int, \App\Services\Import\DTO\NormalizedImportRow>  $rows
     */
    public function preview(array $rows, ImportContext $context): ImportPreview
    {
        $summary = $this->validator->validate($rows, $context);

        $canCommit = $summary->invalidRows === 0 && count($rows) > 0;

        return new ImportPreview(
            context: $context,
            rows: $rows,
            summary: $summary,
            canCommit: $canCommit,
        );
    }

    /**
     * @param  array<int, \App\Services\Import\DTO\NormalizedImportRow>  $rows
     */
    public function commit(array $rows, ImportContext $context): ImportCommit
    {
        return $this->committer->commit($rows, $context);
    }

    /**
     * @param  array<int, \App\Services\Import\DTO\NormalizedImportRow>  $rows
     */
    public function summary(array $rows, ImportContext $context): ImportSummary
    {
        $validation = $this->validator->validate($rows, $context);
        $duplicate = $this->duplicateDetector->detect($rows, $context);

        return new ImportSummary(
            totalRows: $validation->totalRows,
            validRows: $validation->validRows,
            invalidRows: $validation->invalidRows,
            duplicateRows: $duplicate->duplicateRows,
            errors: $validation->errors,
            warnings: $validation->warnings,
        );
    }

    public function template(): ImportTemplate
    {
        return new PesertaImportTemplateExport;
    }
}
