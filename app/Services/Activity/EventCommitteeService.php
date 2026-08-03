<?php

namespace App\Services\Activity;

use App\Models\Activity;
use App\Models\ActivityGroup;
use App\Models\Event;
use App\Models\EventCommitteeAssignment;
use App\Models\EventRole;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Models\Venue;
use App\Support\EventOwnership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EventCommitteeService
{
    public function createRole(array $data): EventRole
    {
        $event = Event::findOrFail($data['event_id']);

        return EventRole::create([
            'event_id' => $event->id,
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'scope' => $data['scope'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    public function assign(array $data): EventCommitteeAssignment
    {
        $event = Event::findOrFail($data['event_id']);
        $role = EventRole::findOrFail($data['event_role_id']);
        $person = Person::findOrFail($data['person_id']);
        $participation = array_key_exists('participation_id', $data) && $data['participation_id'] !== null
            ? Participation::findOrFail($data['participation_id'])
            : null;
        $activityGroup = array_key_exists('activity_group_id', $data) && $data['activity_group_id'] !== null
            ? ActivityGroup::findOrFail($data['activity_group_id'])
            : null;
        $activity = array_key_exists('activity_id', $data) && $data['activity_id'] !== null
            ? Activity::findOrFail($data['activity_id'])
            : null;
        $venue = array_key_exists('venue_id', $data) && $data['venue_id'] !== null
            ? Venue::findOrFail($data['venue_id'])
            : null;

        if (! EventOwnership::belongsToEvent($role, $event)) {
            throw ValidationException::withMessages(['event_role_id' => 'Role must belong to the same event.']);
        }

        if ($participation !== null && ! EventOwnership::belongsToEvent($participation, $event)) {
            throw ValidationException::withMessages(['participation_id' => 'Participation must belong to the same event.']);
        }

        if ($participation !== null && (int) $participation->person_id !== (int) $person->id) {
            throw ValidationException::withMessages(['person_id' => 'Participation must belong to the same person.']);
        }

        if ($activityGroup !== null && ! EventOwnership::belongsToEvent($activityGroup, $event)) {
            throw ValidationException::withMessages(['activity_group_id' => 'Activity group must belong to the same event.']);
        }

        if ($activity !== null && ! EventOwnership::belongsToEvent($activity, $event)) {
            throw ValidationException::withMessages(['activity_id' => 'Activity must belong to the same event.']);
        }

        if ($venue !== null && ! EventOwnership::belongsToEvent($venue, $event)) {
            throw ValidationException::withMessages(['venue_id' => 'Venue must belong to the same event.']);
        }

        $exists = EventCommitteeAssignment::query()
            ->where('event_id', $event->id)
            ->where('person_id', $person->id)
            ->where('event_role_id', $role->id)
            ->where('participation_id', $participation?->id)
            ->where('activity_group_id', $activityGroup?->id)
            ->where('activity_id', $activity?->id)
            ->where('venue_id', $venue?->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['person_id' => 'Duplicate committee assignment.']);
        }

        return EventCommitteeAssignment::create([
            'event_id' => $event->id,
            'person_id' => $person->id,
            'participation_id' => $participation?->id,
            'event_role_id' => $role->id,
            'activity_group_id' => $activityGroup?->id,
            'activity_id' => $activity?->id,
            'venue_id' => $venue?->id,
            'assigned_at' => $data['assigned_at'] ?? now(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Assign a person to an event role and ensure they have a login account.
     *
     * Runs assignment and auto-created user inside a single transaction.
     *
     * @return array{
     *     assignment: EventCommitteeAssignment,
     *     user: User,
     *     user_created: bool,
     *     plain_password: string|null,
     * }
     */
    public function assignAndEnsureUser(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $assignment = $this->assign($data);

            $result = $this->ensureUserForPerson($assignment->person);

            return [
                'assignment' => $assignment,
                'user' => $result['user'],
                'user_created' => $result['created'],
                'plain_password' => $result['plain_password'],
            ];
        });
    }

    /**
     * Create a login account for a person when they do not have one yet.
     *
     * @return array{user: User, created: bool, plain_password: string|null}
     */
    public function ensureUserForPerson(Person $person): array
    {
        $existing = $person->user;

        if ($existing !== null) {
            return [
                'user' => $existing,
                'created' => false,
                'plain_password' => null,
            ];
        }

        $username = $this->buildUniqueUsername($person->nama);
        $plainPassword = $this->buildPasswordFromBirthDate($person);

        $user = User::create([
            'person_id' => $person->id,
            'name' => $person->nama,
            'username' => $username,
            'password' => Hash::make($plainPassword),
            'email' => null,
            'is_active' => true,
        ]);

        return [
            'user' => $user,
            'created' => true,
            'plain_password' => $plainPassword,
        ];
    }

    private function buildUniqueUsername(string $nama): string
    {
        $base = Str::slug($nama, separator: '.');
        $base = (string) preg_replace('/[^a-z0-9.]+/', '', $base);
        $base = trim($base, '.');

        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $suffix = 2;

        while (User::where('username', $username)->exists()) {
            $username = $base . $suffix;
            $suffix++;
        }

        return $username;
    }

    private function buildPasswordFromBirthDate(Person $person): string
    {
        if ($person->tanggal_lahir === null) {
            Log::warning('Auto-create user: person tanpa tanggal lahir, menggunakan password sementara.', [
                'person_id' => $person->id,
                'nama' => $person->nama,
            ]);

            return '12345678';
        }

        return $person->tanggal_lahir->format('dmY');
    }
}
