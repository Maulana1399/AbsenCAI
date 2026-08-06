<?php

use App\Models\desa;
use App\Services\Import\Adapters\ImportAdapter;
use App\Services\Import\Contracts\ImportTemplate;
use App\Services\Import\DTO\ImportContext;
use App\Services\Import\Exceptions\ImportDefinitionNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(Tests\TestCase::class, RefreshDatabase::class);

function adapter_csv(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('desa.csv', $content);
}

beforeEach(function () {
    $this->adapter = app(ImportAdapter::class);
    $this->actingAs(\App\Models\User::factory()->create(['role' => \App\Enums\Role::SuperAdmin]));
});

test('adapter preview runs the pipeline without committing', function () {
    $result = $this->adapter->preview('desa', adapter_csv("desa\nDesa Satu\nDesa Dua\n"));

    expect($result->status)->toBe('previewed')
        ->and($result->commit)->toBeNull()
        ->and($result->rows)->toHaveCount(2)
        ->and($result->summary->totalRows)->toBe(2)
        ->and($result->summary->validRows)->toBe(2);

    $this->assertDatabaseCount('desas', 0);
});

test('adapter commit runs the pipeline and creates records', function () {
    $result = $this->adapter->commit('desa', adapter_csv("desa\nDesa Satu\nDesa Dua\n"));

    expect($result->status)->toBe('completed')
        ->and($result->commit)->not->toBeNull()
        ->and($result->summary->createdRows)->toBe(2);

    $this->assertDatabaseHas('desas', ['desa_asal' => 'Desa Satu']);
    $this->assertDatabaseHas('desas', ['desa_asal' => 'Desa Dua']);
});

test('adapter commit skips duplicates on re-import', function () {
    desa::create(['desa_asal' => 'Desa Satu']);

    $result = $this->adapter->commit('desa', adapter_csv("desa\nDesa Satu\nDesa Baru\n"));

    expect($result->summary->createdRows)->toBe(1)
        ->and($result->summary->skippedRows)->toBe(1)
        ->and($result->commit->skippedIds)->toBe([2]);

    $this->assertSame(1, desa::where('desa_asal', 'Desa Satu')->count());
    $this->assertDatabaseHas('desas', ['desa_asal' => 'Desa Baru']);
});

test('adapter preview prunes blank lines and reports clean validation', function () {
    $result = $this->adapter->preview('desa', adapter_csv("desa\n\nDesa Satu\n"));

    expect($result->rows)->toHaveCount(1)
        ->and($result->summary->totalRows)->toBe(1)
        ->and($result->summary->invalidRows)->toBe(0)
        ->and($result->summary->errors)->toHaveCount(0);
});

test('adapter resolves the definition from the registry', function () {
    expect($this->adapter->definition('desa')->key())->toBe('desa');
});

test('adapter template returns the module template', function () {
    $template = $this->adapter->template('desa');

    expect($template)->toBeInstanceOf(ImportTemplate::class)
        ->and($template->fileName())->toBe('template_import_desa.xlsx');
});

test('adapter throws typed exception for unknown definition', function () {
    $this->adapter->definition('tidak-ada');
})->throws(ImportDefinitionNotFoundException::class);

test('adapter accepts a custom ImportContext', function () {
    $context = new ImportContext(
        type: 'desa',
        userId: 1,
        fileName: 'custom.csv',
        source: 'test',
        mode: 'execute',
        options: [],
        definitionKey: 'desa',
    );

    $result = $this->adapter->commit('desa', adapter_csv("desa\nDesa Context\n"), $context);

    expect($result->commit->context)->toBe($context)
        ->and($result->summary->createdRows)->toBe(1);
});
