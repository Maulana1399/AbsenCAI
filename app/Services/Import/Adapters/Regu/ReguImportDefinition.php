<?php

namespace App\Services\Import\Adapters\Regu;

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
use App\Services\Import\Template\ReguImportTemplateExport;

/**
 * Regu import definition (IF-05).
 *
 * Regu is a global entity (no event/desa/kelompok FK) — no wizard parameter.
 * Business rules follow the legacy `ReguImport`: regu name unique, jenis_kelamin
 * enum `Laki - Laki` / `Perempuan` with dash/case normalization.
 */
final class ReguImportDefinition implements ImportDefinition, ImportDefinitionMetadata
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
        return 'regu';
    }

    public function label(): string
    {
        return 'Import Regu';
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
        return 'Import Regu';
    }

    public function description(): string
    {
        return 'Import data regu dari file CSV atau Excel.';
    }

    public function icon(): string
    {
        return 'flag';
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
            'regu' => ['label' => 'Nama Regu', 'required' => true, 'example' => 'Regu A'],
            'jenis_kelamin' => ['label' => 'Jenis Kelamin', 'required' => true, 'example' => 'Laki - Laki'],
        ];
    }

    public function rules(): array
    {
        return [
            'regu' => 'required|string',
            'jenis_kelamin' => 'required|in:Laki - Laki,Perempuan',
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

        if (trim($row['regu'] ?? '') === '') {
            $errors[] = 'Nama regu wajib diisi.';
        }

        if (! in_array(trim($row['jenis_kelamin'] ?? ''), ['Laki - Laki', 'Perempuan'], true)) {
            $errors[] = 'Jenis kelamin harus Laki - laki atau Perempuan.';
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
        return new ReguImportTemplateExport;
    }
}
