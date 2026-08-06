<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Grant an event role to a user by role code and make that event the active one.
 *
 * Links the user to a Person when they do not have one yet, creates the event
 * role (default permissions auto-fill from the role code), creates the committee
 * assignment, and sets the active event context.
 */
function grantEventRoleToUser(\App\Models\User $user, \App\Models\Event $event, string $roleCode): \App\Models\EventRole
{
    if ($user->person_id === null) {
        $person = \App\Models\Person::create(['nama' => 'Grant Person '.str()->random(6)]);
        $user->forceFill(['person_id' => $person->id])->save();
    }

    $role = \App\Models\EventRole::create([
        'event_id' => $event->id,
        'name' => $roleCode,
        'code' => $roleCode,
    ]);

    \App\Models\EventCommitteeAssignment::create([
        'event_id' => $event->id,
        'person_id' => $user->person_id,
        'event_role_id' => $role->id,
    ]);

    app(\App\Support\ActiveEventContext::class)->set($event);

    return $role;
}
