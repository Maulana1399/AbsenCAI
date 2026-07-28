<?php

use App\Models\CompetitionAnnouncement;
use App\Models\CompetitionCategory;
use App\Models\CompetitionClass;
use App\Models\CompetitionOutcome;
use App\Models\CompetitionRegistration;
use App\Models\CompetitionSchedule;
use App\Models\Event;
use App\Models\Participation;
use App\Models\Person;
use App\Models\User;
use App\Models\Venue;
use App\Services\Competition\CompetitionRegistrationService;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'super_admin']);
    actingAs($this->user);

    $this->event = Event::create([
        'name' => 'Test Competition Event',
        'slug' => 'test-competition',
        'event_type' => 'competition',
        'status' => 'active',
    ]);

    app(\App\Support\ActiveEventContext::class)->set($this->event);

    $this->category = CompetitionCategory::create([
        'event_id' => $this->event->id,
        'name' => 'Lomba Adzan',
    ]);

    $this->class = CompetitionClass::create([
        'event_id' => $this->event->id,
        'competition_category_id' => $this->category->id,
        'name' => 'Class A',
        'gender' => 'M',
    ]);

    $this->person = Person::create([
        'nama' => 'Ahmad Test',
        'jenis_kelamin' => 'L',
    ]);
});

test('1. full competition registration flow', function () {
    $service = app(CompetitionRegistrationService::class);
    $result = $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $this->class->id,
    );

    expect($result['person']->id)->toBe($this->person->id);
    expect($result['status'])->toBe('registered');

    assertDatabaseHas('competition_registrations', [
        'participation_id' => $result['participation']->id,
        'competition_category_id' => $this->category->id,
        'competition_class_id' => $this->class->id,
    ]);
});

test('2. reuse existing person does not create duplicate person', function () {
    $service = app(CompetitionRegistrationService::class);

    $secondClass = CompetitionClass::create([
        'event_id' => $this->event->id,
        'competition_category_id' => $this->category->id,
        'name' => 'SMP Putra',
        'gender' => 'L',
    ]);

    $first = $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $this->class->id,
    );

    $second = $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $secondClass->id,
    );

    expect(Person::where('nama', 'Ahmad Test')->count())->toBe(1);
    expect($first['person']->id)->toBe($this->person->id);
    expect($second['person']->id)->toBe($this->person->id);
});

test('3. duplicate registration throws validation error', function () {
    $service = app(CompetitionRegistrationService::class);

    $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $this->class->id,
    );

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $this->class->id,
    );
});

test('4. schedule status transitions', function () {
    $venue = Venue::create([
        'event_id' => $this->event->id,
        'name' => 'Venue Test',
    ]);

    $schedule = CompetitionSchedule::create([
        'competition_class_id' => $this->class->id,
        'venue_id' => $venue->id,
        'status' => 'Scheduled',
    ]);

    expect($schedule->status)->toBe('Scheduled');

    $schedule->update(['status' => 'Ready']);
    expect($schedule->refresh()->status)->toBe('Ready');

    $schedule->update(['status' => 'NowPlaying']);
    expect($schedule->refresh()->status)->toBe('NowPlaying');

    $schedule->update(['status' => 'Finished']);
    expect($schedule->refresh()->status)->toBe('Finished');
});

test('5. outcome save and retrieve', function () {
    $service = app(CompetitionRegistrationService::class);
    $result = $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $this->class->id,
    );

    $registration = $result['competition_registration'];

    CompetitionOutcome::create([
        'competition_registration_id' => $registration->id,
        'position' => 1,
        'status' => 'Lolos',
        'score' => 95.50,
        'remarks' => 'Juara 1',
    ]);

    assertDatabaseHas('competition_outcomes', [
        'competition_registration_id' => $registration->id,
        'position' => 1,
    ]);
});

test('6. announcement publish and retrieve', function () {
    $announcement = CompetitionAnnouncement::create([
        'event_id' => $this->event->id,
        'message' => 'Test pengumuman',
        'is_active' => true,
        'expires_at' => now()->addMinutes(5),
    ]);

    expect($announcement->is_active)->toBeTrue();

    $active = CompetitionAnnouncement::active()
        ->where('event_id', $this->event->id)
        ->first();

    expect($active)->not->toBeNull();
    expect($active->message)->toBe('Test pengumuman');
});

test('7. viewer schedule grouping by status', function () {
    $venue = Venue::create(['event_id' => $this->event->id, 'name' => 'Venue A']);

    CompetitionSchedule::create([
        'competition_class_id' => $this->class->id,
        'venue_id' => $venue->id,
        'status' => 'NowPlaying',
        'start_at' => now(),
    ]);

    CompetitionSchedule::create([
        'competition_class_id' => $this->class->id,
        'venue_id' => $venue->id,
        'status' => 'Ready',
    ]);

    CompetitionSchedule::create([
        'competition_class_id' => $this->class->id,
        'venue_id' => $venue->id,
        'status' => 'Scheduled',
        'start_at' => now()->addHour(),
    ]);

    $schedules = CompetitionSchedule::where('competition_class_id', $this->class->id)->get();

    expect($schedules->where('status', 'NowPlaying')->count())->toBe(1);
    expect($schedules->where('status', 'Ready')->count())->toBe(1);
    expect($schedules->where('status', 'Scheduled')->count())->toBe(1);
});

test('8. relationship — class belongs to category', function () {
    expect($this->class->competitionCategory->id)->toBe($this->category->id);
    expect($this->category->competitionClasses->pluck('id'))->toContain($this->class->id);
});

test('9. relationship — registration belongs to participation', function () {
    $service = app(CompetitionRegistrationService::class);
    $result = $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $this->class->id,
    );

    $reg = CompetitionRegistration::with('participation')->find($result['competition_registration']->id);

    expect($reg)->not->toBeNull();
    expect($reg->participation->id)->toBe($result['participation']->id);
    expect($reg->competitionCategory->id)->toBe($this->category->id);
    expect($reg->competitionClass->id)->toBe($this->class->id);
});

test('10. event has competition categories', function () {
    expect($this->event->competitionCategories->pluck('id'))->toContain($this->category->id);
});

test('11. venue has competition schedules', function () {
    $venue = Venue::create(['event_id' => $this->event->id, 'name' => 'Venue B']);

    $schedule = CompetitionSchedule::create([
        'competition_class_id' => $this->class->id,
        'venue_id' => $venue->id,
        'status' => 'Scheduled',
    ]);

    expect($venue->fresh()->competitionSchedules->pluck('id'))->toContain($schedule->id);
});

test('13. viewer route returns 200 for active competition event', function () {
    $response = $this->get(route('competition.viewer', ['event' => $this->event]));
    $response->assertStatus(200);
});

test('14. viewer route with venue filter returns 200', function () {
    $venue = Venue::create(['event_id' => $this->event->id, 'name' => 'Venue C']);
    $response = $this->get(route('competition.viewer', ['event' => $this->event, 'venue' => $venue->id]));
    $response->assertStatus(200);
});

test('15. viewer tv mode query parameter works', function () {
    $response = $this->get(route('competition.viewer', ['event' => $this->event, 'display' => 'tv']));
    $response->assertStatus(200);
});

test('16. viewer returns 404 for non-competition event', function () {
    $caiEvent = Event::create(['name' => 'CAI', 'slug' => 'cai-test', 'event_type' => 'cai', 'status' => 'active']);
    $response = $this->get(route('competition.viewer', ['event' => $caiEvent]));
    $response->assertStatus(404);
});

test('17. viewer returns 404 for inactive event', function () {
    $inactiveEvent = Event::create(['name' => 'Archived', 'slug' => 'archived', 'event_type' => 'competition', 'status' => 'archived']);
    $response = $this->get(route('competition.viewer', ['event' => $inactiveEvent]));
    $response->assertStatus(404);
});

test('18. venue filter with filterByVenue updates venue', function () {
    $component = Livewire::test(\App\Livewire\Competition\Viewer::class, ['event' => $this->event]);

    $component->assertSet('venueId', null);

    $venue = Venue::create(['event_id' => $this->event->id, 'name' => 'Venue D']);
    $component->call('filterByVenue', $venue->id);
    $component->assertSet('venueId', (string) $venue->id);
});

test('19. venue filter clears venue', function () {
    $venue = Venue::create(['event_id' => $this->event->id, 'name' => 'Venue E']);
    $component = Livewire::test(\App\Livewire\Competition\Viewer::class, ['event' => $this->event, 'venue' => $venue->id]);

    $component->assertSet('venueId', (string) $venue->id);
    $component->call('filterByVenue');
    $component->assertSet('venueId', null);
});

test('12. event has competition announcements', function () {
    CompetitionAnnouncement::create([
        'event_id' => $this->event->id,
        'message' => 'Test',
        'is_active' => true,
    ]);

    expect($this->event->fresh()->competitionAnnouncements->count())->toBe(1);
});

test('20. create and assign schedule entry', function () {
    $schedule = CompetitionSchedule::create([
        'competition_class_id' => $this->class->id,
        'status' => 'NowPlaying',
    ]);

    $service = app(\App\Services\Competition\CompetitionRegistrationService::class);
    $reg = $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $this->class->id,
    );

    $entry = \App\Models\CompetitionScheduleEntry::create([
        'competition_schedule_id' => $schedule->id,
        'competition_registration_id' => $reg['competition_registration']->id,
    ]);

    expect($entry->id)->toBeGreaterThan(0);
    expect($entry->competitionSchedule->id)->toBe($schedule->id);
    expect($entry->competitionRegistration->id)->toBe($reg['competition_registration']->id);
});

test('21. schedule entry relationships', function () {
    $schedule = CompetitionSchedule::create([
        'competition_class_id' => $this->class->id,
        'status' => 'NowPlaying',
    ]);

    $service = app(\App\Services\Competition\CompetitionRegistrationService::class);
    $reg = $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $this->class->id,
    );

    \App\Models\CompetitionScheduleEntry::create([
        'competition_schedule_id' => $schedule->id,
        'competition_registration_id' => $reg['competition_registration']->id,
    ]);

    expect($schedule->fresh()->scheduleEntries)->toHaveCount(1);
    expect($reg['competition_registration']->fresh()->scheduleEntries)->toHaveCount(1);
});

test('22. assign and unassign in entry manager', function () {
    $schedule = CompetitionSchedule::create([
        'competition_class_id' => $this->class->id,
        'status' => 'NowPlaying',
    ]);

    $service = app(\App\Services\Competition\CompetitionRegistrationService::class);
    $reg = $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $this->class->id,
    );

    $component = Livewire::test(\App\Livewire\Competition\Schedule\EntryManager::class, ['schedule' => $schedule]);

    $component->assertSet('assigned', []);
    $component->call('assign', $reg['competition_registration']->id);
    $component->assertSet('assigned.0.id', $reg['competition_registration']->id);
    $component->call('unassign', $reg['competition_registration']->id);
    $component->assertSet('assigned', []);
});

test('24. gender validation prevents female in male class', function () {
    $maleClass = CompetitionClass::create([
        'event_id' => $this->event->id,
        'competition_category_id' => $this->category->id,
        'name' => 'Test Putra',
        'gender' => 'L',
    ]);

    $femalePerson = Person::create(['nama' => 'Siti Test', 'jenis_kelamin' => 'P']);
    $service = app(\App\Services\Competition\CompetitionRegistrationService::class);

    $this->expectException(\Illuminate\Validation\ValidationException::class);
    $service->registerForPerson(
        person: $femalePerson,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $maleClass->id,
    );
});

test('25. gender validation allows female in female class', function () {
    $femaleClass = CompetitionClass::create([
        'event_id' => $this->event->id,
        'competition_category_id' => $this->category->id,
        'name' => 'Test Putri',
        'gender' => 'P',
    ]);

    $femalePerson = Person::create(['nama' => 'Siti Test2', 'jenis_kelamin' => 'P']);
    $service = app(\App\Services\Competition\CompetitionRegistrationService::class);

    $result = $service->registerForPerson(
        person: $femalePerson,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $femaleClass->id,
    );

    expect($result['status'])->toBe('registered');
});

test('25b. gender validation mixed class accepts everyone', function () {
    $mixedClass = CompetitionClass::create([
        'event_id' => $this->event->id,
        'competition_category_id' => $this->category->id,
        'name' => 'Test Campuran',
        'gender' => 'M',
    ]);

    $male = Person::create(['nama' => 'Budi Mixed', 'jenis_kelamin' => 'L']);
    $female = Person::create(['nama' => 'Dewi Mixed', 'jenis_kelamin' => 'P']);
    $service = app(\App\Services\Competition\CompetitionRegistrationService::class);

    $r1 = $service->registerForPerson(person: $male, eventId: $this->event->id, competitionCategoryId: $this->category->id, competitionClassId: $mixedClass->id);
    $r2 = $service->registerForPerson(person: $female, eventId: $this->event->id, competitionCategoryId: $this->category->id, competitionClassId: $mixedClass->id);

    expect($r1['status'])->toBe('registered');
    expect($r2['status'])->toBe('registered');
});

test('26. multi-class registration - same person different classes', function () {
    $classB = CompetitionClass::create([
        'event_id' => $this->event->id,
        'competition_category_id' => $this->category->id,
        'name' => 'Kelas B',
        'gender' => 'L',
    ]);

    $service = app(\App\Services\Competition\CompetitionRegistrationService::class);

    $first = $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $this->class->id,
    );

    $second = $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $classB->id,
    );

    expect($first['status'])->toBe('registered');
    expect($second['status'])->toBe('registered');
    expect($first['participation']->id)->toBe($second['participation']->id);
});

test('27. viewer shows empty state when no schedule entries', function () {
    $schedule = CompetitionSchedule::create([
        'competition_class_id' => $this->class->id,
        'status' => 'NowPlaying',
    ]);

    expect($schedule->scheduleEntries)->toHaveCount(0);
});

test('28. viewer shows participants when schedule entries exist', function () {
    $schedule = CompetitionSchedule::create([
        'competition_class_id' => $this->class->id,
        'status' => 'NowPlaying',
    ]);

    $service = app(\App\Services\Competition\CompetitionRegistrationService::class);
    $reg = $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $this->class->id,
    );

    \App\Models\CompetitionScheduleEntry::create([
        'competition_schedule_id' => $schedule->id,
        'competition_registration_id' => $reg['competition_registration']->id,
    ]);

    $schedule->load('scheduleEntries.competitionRegistration.participation.person');
    expect($schedule->scheduleEntries)->toHaveCount(1);
    expect($schedule->scheduleEntries->first()->competitionRegistration->participation->person->nama)->toBe('Ahmad Test');
});

test('23. schedule report includes participant count', function () {
    $schedule = CompetitionSchedule::create([
        'competition_class_id' => $this->class->id,
        'status' => 'NowPlaying',
    ]);

    $service = app(\App\Services\Competition\CompetitionRegistrationService::class);
    $reg = $service->registerForPerson(
        person: $this->person,
        eventId: $this->event->id,
        competitionCategoryId: $this->category->id,
        competitionClassId: $this->class->id,
    );

    \App\Models\CompetitionScheduleEntry::create([
        'competition_schedule_id' => $schedule->id,
        'competition_registration_id' => $reg['competition_registration']->id,
    ]);

    $report = app(\App\Services\Competition\CompetitionReportService::class);
    $schedules = $report->scheduleReport($this->event);

    $entry = $schedules->firstWhere('id', $schedule->id);
    expect($entry)->not->toBeNull();
    expect($entry->participants_count)->toBe(1);
});
