<?php

namespace App\Services\Import\Contracts;

use App\Services\Import\DTO\ImportContext;

interface ImportDefinition
{
    public function key(): string;

    public function label(): string;

    public function parser(): ImportParser;

    public function validator(): ImportValidator;

    public function normalizer(): ImportNormalizer;

    public function duplicateDetector(): ImportDuplicateDetector;

    public function committer(): ImportCommitter;

    public function activityLogger(): ImportActivityLogger;

    public function supportedVersion(): string;

    public function minimumVersion(): string;

    public function currentVersion(): string;

    public function supportsPreview(ImportContext $context): bool;

    public function supportsCommit(ImportContext $context): bool;
}
