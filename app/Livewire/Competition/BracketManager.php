<?php

namespace App\Livewire\Competition;

use App\Models\CompetitionBracket;
use App\Models\CompetitionBracketMatch;
use App\Models\CompetitionClass;
use App\Models\CompetitionSchedule;
use App\Models\Venue;
use App\Support\ActiveEventContext;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class BracketManager extends Component
{
    public string $filterClassId = '';
    public ?int $selectedBracketId = null;

    public string $newParticipantCount = '8';

    public function mount(): void
    {
        app(ActiveEventContext::class)->requireCurrent();
    }

    public function selectBracket(int $bracketId): void
    {
        $this->selectedBracketId = $bracketId;
    }

    public function generate(int $classId): void
    {
        Gate::authorize('manage-events');

        $class = CompetitionClass::findOrFail($classId);
        $count = (int) $this->newParticipantCount;

        if (!in_array($count, [4, 8, 16, 32])) {
            session()->flash('error', 'Participant count must be 4, 8, 16, or 32.');
            return;
        }

        $existing = CompetitionBracket::where('competition_class_id', $classId)
            ->whereIn('status', ['draft', 'active'])
            ->exists();

        if ($existing) {
            session()->flash('error', 'An active bracket already exists for this class.');
            return;
        }

        $bracket = CompetitionBracket::create([
            'competition_class_id' => $classId,
            'name' => $class->name . ' Bracket',
            'participant_count' => $count,
            'status' => 'active',
        ]);

        $totalRounds = (int) log($count, 2);

        for ($round = $totalRounds; $round >= 1; $round--) {
            $matchesInRound = (int) pow(2, $round - 1);

            for ($pos = 1; $pos <= $matchesInRound; $pos++) {
                $schedule = CompetitionSchedule::create([
                    'competition_class_id' => $classId,
                    'status' => 'Scheduled',
                    'required_participants' => 2,
                    'sort_order' => ($totalRounds - $round) * 100 + $pos,
                ]);

                $bracketMatch = new CompetitionBracketMatch([
                    'competition_bracket_id' => $bracket->id,
                    'competition_schedule_id' => $schedule->id,
                    'round' => $round,
                    'position' => $pos,
                ]);

                if ($round < $totalRounds) {
                    $prevMatchesCount = (int) pow(2, $round);
                    $bracketMatch->source_match_a_id = $this->findBracketMatch($bracket->id, $round + 1, $pos * 2 - 1);
                    $bracketMatch->source_match_b_id = $this->findBracketMatch($bracket->id, $round + 1, $pos * 2);
                }

                $bracketMatch->save();
            }
        }

        $this->selectedBracketId = $bracket->id;
        session()->flash('success', "Bracket generated with {$count} participants.");
    }

    private function findBracketMatch(int $bracketId, int $round, int $position): ?int
    {
        $match = CompetitionBracketMatch::where('competition_bracket_id', $bracketId)
            ->where('round', $round)
            ->where('position', $position)
            ->first();
        return $match?->id;
    }

    private function getRoundLabel(int $round, int $totalRounds): string
    {
        if ($round === 1) return 'Final';
        if ($round === 2) return 'Semi Final';
        if ($round === 3) return 'Quarter Final';
        $roundNum = $totalRounds - $round + 1;
        return "Round of " . pow(2, $totalRounds - $round + 1);
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();

        $classes = CompetitionClass::where('event_id', $event?->id)
            ->where('is_active', true)
            ->orderBy('sort_order')->orderBy('name')
            ->get();

        $brackets = CompetitionBracket::with('competitionClass')
            ->whereIn('competition_class_id', $classes->pluck('id'))
            ->orderBy('created_at', 'desc')
            ->get();

        $selectedBracket = null;
        $bracketRounds = [];

        if ($this->selectedBracketId) {
            $selectedBracket = CompetitionBracket::with([
                'bracketMatches.schedule.scheduleEntries.competitionRegistration.participation.person',
                'bracketMatches.schedule.winner.participation.person',
                'bracketMatches.sourceMatchA',
                'bracketMatches.sourceMatchB',
            ])->find($this->selectedBracketId);

            if ($selectedBracket) {
                $totalRounds = (int) log($selectedBracket->participant_count, 2);
                $matches = $selectedBracket->bracketMatches->groupBy('round')->sortKeysDesc();

                foreach ($matches as $round => $roundMatches) {
                    $bracketRounds[] = [
                        'label' => $this->getRoundLabel($round, $totalRounds),
                        'round' => $round,
                        'matches' => $roundMatches->sortBy('position')->values(),
                    ];
                }
            }
        }

        return view('livewire.competition.bracket-manager', [
            'classes' => $classes,
            'brackets' => $brackets,
            'selectedBracket' => $selectedBracket,
            'bracketRounds' => $bracketRounds,
        ]);
    }
}
