<?php

namespace App\Services\Import\Support;

final class TemplateVersion
{
    public function __construct(
        public readonly string $name,
        public readonly string $version,
    ) {
    }
}
