<?php

namespace App\Services\Import\Contracts;

use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Results\ImportSummary;

/**
 * Module-facing metadata & wizard configuration exposed by a definition.
 *
 * The wizard reads these instead of hardcoding module details:
 *  - displayName / description / icon : branding
 *  - parameters()                     : inputs the wizard must render (e.g. Desa select)
 *  - parameterOptions()               : option list for select parameters
 *  - columns() / rules()              : preview table + row validation
 *  - template() / summary()           : generator + aggregated summary
 */
interface ImportDefinitionMetadata
{
    public function displayName(): string;

    public function description(): string;

    public function icon(): string;

    /**
     * Wizard parameters keyed by name.
     *
     * @return array<string, array{label: string, type: string, required: bool}>
     */
    public function parameters(): array;

    /**
     * Preview columns keyed by data key.
     *
     * @return array<string, array{label: string, required: bool, example: string}>
     */
    public function columns(): array;

    /**
     * @return array<string, string>
     */
    public function rules(): array;

    public function template(): ImportTemplate;

    /**
     * @param  array<int, \App\Services\Import\DTO\NormalizedImportRow>  $rows
     */
    public function summary(array $rows, ImportContext $context): ImportSummary;

    /**
     * Options for a select parameter (value => label).
     *
     * @return array<int|string, string>
     */
    public function parameterOptions(string $key, ImportContext $context): array;
}
