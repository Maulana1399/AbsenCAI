<?php

use App\Enums\Role;
use App\Models\CompetitionBracket;
use App\Models\CompetitionBracketMatch;
use App\Models\CompetitionCategory;
use App\Models\CompetitionClass;
use App\Models\CompetitionSchedule;
use App\Models\CompetitionScheduleEntry;
use App\Models\CompetitionTeam;
use App\Models\CompetitionTeamOutcome;
use App\Models\Event;
use App\Models\User;
use App\Models\kelompok;
use App\Services\Competition\CompetitionResultService;
use App\Services\Competition\CompetitionWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ctbp_event(array $overrides = []): Event
{
    return Event::create(array_merge([
        'name' => 'CTBP Event '.str()->random(6),
        'slug' => 'ctbp-'.str()->random(6),
        'status' => 'active',
        'event_type' => 'competition',
    ], $overrides));
}

function ctbp_category(Event $event): CompetitionCategory
{
    return CompetitionCategory::create(['event_id' => $event->id, 'name' => 'CTBP Cat '.str()->random(4)]);
}

function ctbp_class(Event $event, CompetitionCategory $category): CompetitionClass
{
    return CompetitionClass::create([
        'event_id' => $event->id,
        'competition_category_id' => $category->id,
        'name' => 'CTBP VS Team '.str()->random(4),
        'gender' => 'M',
        'format' => 'team_vs_team',
        'status' => 'registration_open',
        'is_active' => true,
    ]);
}

function ctbp_kelompok(string $name): kelompok
{
    return kelompok::create(['kelompok_asal' => $name]);
}

function ctbp_team(Event $event, CompetitionClass $class, ?kelompok $kelompok, string $name): CompetitionTeam
{
    return CompetitionTeam::create([
        'event_id' => $event->id,
        'competition_class_id' => $class->id,
        'name' => $name,
        'kelompok_id' => $kelompok?->id,
        'is_active' => true,
    ]);
}

function ctbp_generateBracket(CompetitionClass $class, int $count): CompetitionBracket
{
    \Livewire::test(\App\Livewire\Competition\BracketManager::class)
        ->set('newParticipantCount', (string) $count)
        ->call('generate', $class->id);

    return CompetitionBracket::where('competition_class_id', $class->id)->first();
}

function ctbp_teamEntry(CompetitionBracketMatch $match, CompetitionTeam $team, int $order): void
{
    CompetitionScheduleEntry::create([
        'competition_schedule_id' => $match->schedule->id,
        'competition_team_id' => $team->id,
        'order_number' => $order,
    ]);
}

function ctbp_finishTeamMatch(CompetitionBracketMatch $match, int $winnerTeamId): void
{
    $workflow = app(CompetitionWorkflowService::class);
    $schedule = $match->schedule;

    if ($schedule->status === 'Scheduled') {
        $workflow->prepareMatch($schedule);
    }
    if ($schedule->status === 'Ready') {
        $workflow->startMatch($schedule);
    }
    if ($schedule->status === 'Playing') {
        $workflow->moveToWaitingResult($schedule);
    }

    $workflow->submitTeamResult($schedule->fresh(), $winnerTeamId, 'Normal', null);
}

// ---------------------------------------------------------------------------
// 4-team bracket → team Juara 1/2/3
// ---------------------------------------------------------------------------

test('finishing the team bracket final writes team Juara 1/2/3 and advances winners', function () {
    $admin = User::factory()->create(['role' => Role::SuperAdmin]);
    $event = ctbp_event();
    app(\App\Support\ActiveEventContext::class)->set($event);
    $this->actingAs($admin);

    $category = ctbp_category($event);
    $class = ctbp_class($event, $category);
    $bracket = ctbp_generateBracket($class, 4);

    $matches = $bracket->bracketMatches;
    $semi1 = $matches->where('round', 2)->where('position', 1)->first();
    $semi2 = $matches->where('round', 2)->where('position', 2)->first();
    $final = $matches->where('round', 1)->first();

    $teamA = ctbp_team($event, $class, ctbp_kelompok('KM 7'), 'KM 7');
    $teamB = ctbp_team($event, $class, ctbp_kelompok('KM 10'), 'KM 10');
    $teamC = ctbp_team($event, $class, ctbp_kelompok('KM 12'), 'KM 12');
    $teamD = ctbp_team($event, $class, ctbp_kelompok('KM 15'), 'KM 15');

    ctbp_teamEntry($semi1, $teamA, 1);
    ctbp_teamEntry($semi1, $teamB, 2);
    ctbp_teamEntry($semi2, $teamC, 1);
    ctbp_teamEntry($semi2, $teamD, 2);

    ctbp_finishTeamMatch($semi1, $teamA->id); // A advances
    ctbp_finishTeamMatch($semi2, $teamC->id); // C advances

    // Final now contains both advanced teams.
    $finalTeamIds = $final->schedule->fresh()->scheduleEntries()->pluck('competition_team_id')->sort()->values()->all();
    expect($finalTeamIds)->toBe([$teamA->id, $teamC->id]);

    ctbp_finishTeamMatch($final, $teamA->id); // A juara 1

    $posByTeam = CompetitionTeamOutcome::pluck('position', 'competition_team_id');
    expect($posByTeam->get($teamA->id))->toBe(1)
        ->and($posByTeam->get($teamC->id))->toBe(2)
        ->and($posByTeam->get($teamB->id))->toBe(3)
        ->and($posByTeam->get($teamD->id))->toBe(3)
        ->and($final->schedule->fresh()->winner_team_id)->toBe($teamA->id);
});

test('podiumForTeams returns team Juara 1/2/3 after bracket final', function () {
    $admin = User::factory()->create(['role' => Role::SuperAdmin]);
    $event = ctbp_event();
    app(\App\Support\ActiveEventContext::class)->set($event);
    $this->actingAs($admin);

    $category = ctbp_category($event);
    $class = ctbp_class($event, $category);
    $bracket = ctbp_generateBracket($class, 4);

    $matches = $bracket->bracketMatches;
    $semi1 = $matches->where('round', 2)->where('position', 1)->first();
    $semi2 = $matches->where('round', 2)->where('position', 2)->first();
    $final = $matches->where('round', 1)->first();

    $teamA = ctbp_team($event, $class, ctbp_kelompok('A'), 'Team A');
    $teamB = ctbp_team($event, $class, ctbp_kelompok('B'), 'Team B');
    $teamC = ctbp_team($event, $class, ctbp_kelompok('C'), 'Team C');
    $teamD = ctbp_team($event, $class, ctbp_kelompok('D'), 'Team D');

    ctbp_teamEntry($semi1, $teamA, 1);
    ctbp_teamEntry($semi1, $teamB, 2);
    ctbp_teamEntry($semi2, $teamC, 1);
    ctbp_teamEntry($semi2, $teamD, 2);

    ctbp_finishTeamMatch($semi1, $teamA->id);
    ctbp_finishTeamMatch($semi2, $teamC->id);
    ctbp_finishTeamMatch($final, $teamA->id);

    $podium = app(CompetitionResultService::class)->podiumForTeams($event->id, $class->id);

    expect($podium)->toHaveCount(3)
        ->and($podium[0]['team_name'])->toBe('Team A')
        ->and($podium[1]['team_name'])->toBe('Team C')
        ->and($podium[2]['team_name'])->toBe('Team B');
});

// ---------------------------------------------------------------------------
// OfficialPanel team match
// ---------------------------------------------------------------------------

test('OfficialPanel can submit a team winner for a team match', function () {
    $admin = User::factory()->create(['role' => Role::SuperAdmin]);
    $event = ctbp_event();
    app(\App\Support\ActiveEventContext::class)->set($event);
    $this->actingAs($admin);

    $category = ctbp_category($event);
    $class = ctbp_class($event, $category);
    $bracket = ctbp_generateBracket($class, 4);

    $semi1 = $bracket->bracketMatches->where('round', 2)->where('position', 1)->first();
    $teamA = ctbp_team($event, $class, ctbp_kelompok('KM 7'), 'KM 7');
    $teamB = ctbp_team($event, $class, ctbp_kelompok('KM 10'), 'KM 10');
    ctbp_teamEntry($semi1, $teamA, 1);
    ctbp_teamEntry($semi1, $teamB, 2);

    $semi1->schedule->update(['status' => 'Waiting Result', 'required_participants' => 1]);

    $panel = \Livewire::test(\App\Livewire\Competition\OfficialPanel::class);
    $panel->call('openSubmitDialog', $semi1->schedule->id);
    $available = collect($panel->get('availableParticipants'))->pluck('name')->all();
    expect($available)->toBe(['KM 7', 'KM 10']);

    $panel->set('selectedWinnerId', $teamA->id)
        ->set('finishReason', 'Normal')
        ->call('submitResult')
        ->assertHasNoErrors();

    expect($semi1->schedule->fresh()->winner_team_id)->toBe($teamA->id);
});

// ---------------------------------------------------------------------------
// No-op cases
// ---------------------------------------------------------------------------

test('non-final team match does not finalize team podium', function () {
    $admin = User::factory()->create(['role' => Role::SuperAdmin]);
    $event = ctbp_event();
    app(\App\Support\ActiveEventContext::class)->set($event);
    $this->actingAs($admin);

    $category = ctbp_category($event);
    $class = ctbp_class($event, $category);
    $bracket = ctbp_generateBracket($class, 4);

    $semi1 = $bracket->bracketMatches->where('round', 2)->where('position', 1)->first();
    $teamA = ctbp_team($event, $class, ctbp_kelompok('A'), 'A');
    $teamB = ctbp_team($event, $class, ctbp_kelompok('B'), 'B');
    ctbp_teamEntry($semi1, $teamA, 1);
    ctbp_teamEntry($semi1, $teamB, 2);

    ctbp_finishTeamMatch($semi1, $teamA->id);

    expect(CompetitionTeamOutcome::count())->toBe(0);
});

test('unfinished team final does not finalize team podium', function () {
    $admin = User::factory()->create(['role' => Role::SuperAdmin]);
    $event = ctbp_event();
    app(\App\Support\ActiveEventContext::class)->set($event);
    $this->actingAs($admin);

    $category = ctbp_category($event);
    $class = ctbp_class($event, $category);
    $bracket = ctbp_generateBracket($class, 4);

    $final = $bracket->bracketMatches->where('round', 1)->first();
    $final->schedule->update(['status' => 'Scheduled']);

    app(\App\Services\Competition\CompetitionBracketPodiumService::class)
        ->finalizeTeamPodiumForSchedule($final->schedule);

    expect(CompetitionTeamOutcome::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// reset team match
// ---------------------------------------------------------------------------

test('resetMatch clears team winner and team outcomes', function () {
    $admin = User::factory()->create(['role' => Role::SuperAdmin]);
    $event = ctbp_event();
    app(\App\Support\ActiveEventContext::class)->set($event);
    $this->actingAs($admin);

    $category = ctbp_category($event);
    $class = ctbp_class($event, $category);
    $bracket = ctbp_generateBracket($class, 4);

    $final = $bracket->bracketMatches->where('round', 1)->first();
    $teamA = ctbp_team($event, $class, ctbp_kelompok('A'), 'A');
    $teamB = ctbp_team($event, $class, ctbp_kelompok('B'), 'B');
    ctbp_teamEntry($final, $teamA, 1);
    ctbp_teamEntry($final, $teamB, 2);

    CompetitionTeamOutcome::updateOrCreate(['competition_team_id' => $teamA->id], ['position' => 1]);
    CompetitionTeamOutcome::updateOrCreate(['competition_team_id' => $teamB->id], ['position' => 2]);

    $final->schedule->update(['status' => 'Finished', 'winner_team_id' => $teamA->id]);

    app(CompetitionWorkflowService::class)->resetMatch($final->schedule);

    expect($final->schedule->fresh()->winner_team_id)->toBeNull()
        ->and($final->schedule->fresh()->status)->toBe('Scheduled')
        ->and(CompetitionTeamOutcome::count())->toBe(0);
});
