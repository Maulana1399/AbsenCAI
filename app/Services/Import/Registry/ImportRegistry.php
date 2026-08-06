<?php

namespace App\Services\Import\Registry;

use App\Services\Import\Contracts\ImportDefinition;

final class ImportRegistry
{
    /**
     * @var array<string, ImportDefinition>
     */
    private array $definitions = [];

    public function register(ImportDefinition $definition): void
    {
        $this->definitions[$definition->key()] = $definition;
    }

    public function resolve(string $key): ?ImportDefinition
    {
        return $this->definitions[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    /**
     * @return array<string, ImportDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * @return array<string, ImportDefinition>
     */
    public function definitions(): array
    {
        return $this->definitions;
    }
}
