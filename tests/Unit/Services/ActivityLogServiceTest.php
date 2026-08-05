<?php

use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ---------------------------------------------------------------------------
// authenticated user is recorded
// ---------------------------------------------------------------------------

test('log records authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $log = app(ActivityLogService::class)->log(
        action: 'test',
        module: 'testing',
        description: 'Test log entry',
    );

    expect($log->user_id)->toBe($user->id);
});

// ---------------------------------------------------------------------------
// explicit user can be recorded
// ---------------------------------------------------------------------------

test('log records explicitly provided user', function () {
    $user = User::factory()->create();

    $log = app(ActivityLogService::class)->log(
        action: 'test',
        module: 'testing',
        description: 'Test with explicit user',
        user: $user,
    );

    expect($log->user_id)->toBe($user->id);
});

// ---------------------------------------------------------------------------
// system/anonymous log supports null user_id
// ---------------------------------------------------------------------------

test('log supports anonymous system action with null user', function () {
    $log = app(ActivityLogService::class)->log(
        action: 'system',
        module: 'testing',
        description: 'System action without user',
    );

    expect($log->user_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// action/module/description stored correctly
// ---------------------------------------------------------------------------

test('log stores action module and description correctly', function () {
    $log = app(ActivityLogService::class)->log(
        action: 'created',
        module: 'surat_izin',
        description: 'Surat izin dibuat',
    );

    expect($log->action)->toBe('created')
        ->and($log->module)->toBe('surat_izin')
        ->and($log->description)->toBe('Surat izin dibuat');
});

// ---------------------------------------------------------------------------
// subject_type and subject_id stored
// ---------------------------------------------------------------------------

test('log stores subject type and id when subject provided', function () {
    $user = User::factory()->create();

    $log = app(ActivityLogService::class)->log(
        action: 'test',
        module: 'testing',
        description: 'With subject',
        subject: $user,
    );

    expect($log->subject_type)->toBe(User::class)
        ->and($log->subject_id)->toBe($user->id);
});

// ---------------------------------------------------------------------------
// properties stored and cast correctly
// ---------------------------------------------------------------------------

test('log stores properties as array and casts correctly', function () {
    $properties = ['key' => 'value', 'number' => 42];

    $log = app(ActivityLogService::class)->log(
        action: 'test',
        module: 'testing',
        description: 'With properties',
        properties: $properties,
    );

    expect($log->properties)->toBe($properties);
});

// ---------------------------------------------------------------------------
// deleted user does not delete activity log
// ---------------------------------------------------------------------------

test('deleted user does not delete activity log', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $log = app(ActivityLogService::class)->log(
        action: 'test',
        module: 'testing',
        description: 'Before user deleted',
    );

    $user->delete();

    $this->assertDatabaseHas('activity_logs', ['id' => $log->id]);
    $this->assertDatabaseMissing('activity_logs', ['id' => $log->id, 'user_id' => $user->id]);
});

// ---------------------------------------------------------------------------
// deleted subject does not delete activity log
// ---------------------------------------------------------------------------

test('deleted subject does not delete activity log', function () {
    $user = User::factory()->create();

    $log = app(ActivityLogService::class)->log(
        action: 'test',
        module: 'testing',
        description: 'Subject will be deleted',
        subject: $user,
    );

    $user->delete();

    $this->assertDatabaseHas('activity_logs', [
        'id' => $log->id,
        'subject_type' => User::class,
        'subject_id' => $user->id,
    ]);
});
