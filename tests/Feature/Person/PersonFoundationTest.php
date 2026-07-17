<?php

use App\Models\desa;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function PersonFoundation_makePerson(array $overrides = []): Person
{
    return Person::create(array_merge([
        'nama' => 'Test Person',
    ], $overrides));
}

function PersonFoundation_makeDesa(array $overrides = []): desa
{
    return desa::create(array_merge([
        'desa_asal' => 'Desa Test',
    ], $overrides));
}

function PersonFoundation_makeUser(): User
{
    return User::factory()->create();
}

test('people table has expected columns', function () {
    $columns = Schema::getColumnListing('people');
    $expected = ['id', 'nama', 'jenis_kelamin', 'desa_id', 'nip', 'created_at', 'updated_at'];

    expect($columns)->toMatchArray($expected);

    $nipType = Schema::getColumnType('people', 'nip');
    expect($nipType)->toBe('integer');

    $jenisKelaminType = Schema::getColumnType('people', 'jenis_kelamin');
    expect($jenisKelaminType)->toBe('varchar');
});

test('person can be created with minimal fields', function () {
    $person = PersonFoundation_makePerson(['nama' => 'John Doe']);

    expect($person->exists)->toBeTrue()
        ->and($person->nama)->toBe('John Doe')
        ->and($person->jenis_kelamin)->toBeNull()
        ->and($person->desa_id)->toBeNull()
        ->and($person->nip)->toBeNull();
});

test('person requires a name', function () {
    expect(fn () => PersonFoundation_makePerson(['nama' => null]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('person can have all optional fields', function () {
    $desa = PersonFoundation_makeDesa();
    $person = PersonFoundation_makePerson([
        'nama' => 'Jane Doe',
        'jenis_kelamin' => 'P',
        'desa_id' => $desa->id,
        'nip' => 12345,
    ]);

    expect($person->nama)->toBe('Jane Doe')
        ->and($person->jenis_kelamin)->toBe('P')
        ->and($person->desa_id)->toBe($desa->id)
        ->and($person->nip)->toBe(12345);
});

test('person nip must be unique', function () {
    PersonFoundation_makePerson(['nip' => 10001, 'nama' => 'Person A']);
    expect(fn () => PersonFoundation_makePerson(['nip' => 10001, 'nama' => 'Person B']))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

test('person nip can be null for multiple records', function () {
    PersonFoundation_makePerson(['nip' => null, 'nama' => 'Person A']);
    PersonFoundation_makePerson(['nip' => null, 'nama' => 'Person B']);

    expect(Person::count())->toBe(2);
});

test('person belongs to desa', function () {
    $desa = PersonFoundation_makeDesa(['desa_asal' => 'Desa Indah']);
    $person = PersonFoundation_makePerson([
        'desa_id' => $desa->id,
        'nama' => 'Village Person',
    ]);

    expect($person->desa)->not->toBeNull()
        ->and($person->desa->desa_asal)->toBe('Desa Indah');
});

test('person desa can be null', function () {
    $person = PersonFoundation_makePerson(['desa_id' => null]);

    expect($person->desa)->toBeNull();
});

test('person jenis_kelamin uses L/P format', function () {
    $male = PersonFoundation_makePerson([
        'jenis_kelamin' => 'L',
        'nama' => 'Male Person',
    ]);
    $female = PersonFoundation_makePerson([
        'jenis_kelamin' => 'P',
        'nama' => 'Female Person',
    ]);

    expect($male->jenis_kelamin)->toBe('L')
        ->and($female->jenis_kelamin)->toBe('P');
});

test('person jenis_kelamin label accessor returns full text', function () {
    $male = PersonFoundation_makePerson([
        'jenis_kelamin' => 'L',
        'nama' => 'Male Person',
    ]);
    $female = PersonFoundation_makePerson([
        'jenis_kelamin' => 'P',
        'nama' => 'Female Person',
    ]);
    $none = PersonFoundation_makePerson([
        'jenis_kelamin' => null,
        'nama' => 'No Gender',
    ]);

    expect($male->jenis_kelamin_label)->toBe('Laki - Laki')
        ->and($female->jenis_kelamin_label)->toBe('Perempuan')
        ->and($none->jenis_kelamin_label)->toBe('');
});

test('person uses people table', function () {
    $person = PersonFoundation_makePerson();

    expect($person->getTable())->toBe('people');
});

test('person records can be queried', function () {
    PersonFoundation_makePerson(['nama' => 'Alpha', 'nip' => 1]);
    PersonFoundation_makePerson(['nama' => 'Beta', 'nip' => 2]);
    PersonFoundation_makePerson(['nama' => 'Gamma', 'nip' => 3]);

    expect(Person::count())->toBe(3);

    $first = Person::where('nama', 'Alpha')->first();
    expect($first)->not->toBeNull()
        ->and($first->nip)->toBe(1);
});
