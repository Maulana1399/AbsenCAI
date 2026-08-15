<?php

namespace App\Livewire\Competition\Schedule;

use App\Models\CompetitionHeatResult;
use App\Models\CompetitionOutcome;
use App\Models\CompetitionSchedule;
use App\Models\CompetitionScheduleEntry;
use App\Models\CompetitionTeamOutcome;
use App\Services\Competition\CompetitionResultService;
use App\Support\ActiveEventContext;
use App\Support\CompetitionFormat;
use App\Support\CompetitionResultType;
use App\Support\CompetitionTime;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class OutcomeManager extends Component
{
    public CompetitionSchedule $schedule;

    public array $outcomes = [];

    public array $heatResults = [];

    public array $teamOutcomes = [];

    public bool $isHeat = false;

    public bool $isTeam = false;

    public function mount(CompetitionSchedule $schedule): void
    {
        app(ActiveEventContext::class)->requireCurrent();

        $this->schedule = $schedule->load(['competitionClass.competitionCategory', 'venue']);
        $this->isHeat = $schedule->competitionClass?->format === CompetitionFormat::INDIVIDUAL_HEAT;
        $this->isTeam = $schedule->competitionClass?->isTeamFormat() ?? false;
    }

    public function loadParticipants(): void
    {
        $with = [
            'competitionRegistration.participation.person.desa',
            'competitionRegistration.participation.person.kelompok',
        ];

        if ($this->isTeam) {
            $with[] = 'team.outcome';
        } else {
            $with[] = 'competitionRegistration.outcome';

            if ($this->isHeat) {
                $with[] = 'competitionRegistration.heatResults';
            }
        }

        $entries = CompetitionScheduleEntry::with($with)
            ->where('competition_schedule_id', $this->schedule->id)
            ->orderBy('order_number')
            ->orderBy('id')
            ->get();

        if ($this->isTeam) {
            $this->teamOutcomes = $entries->map(function ($entry) {
                $team = $entry->team;
                if (! $team) {
                    return null;
                }

                $outcome = $team->outcome;

                return [
                    'team_id' => $team->id,
                    'team_name' => $team->name,
                    'position' => $outcome?->position ?? '',
                    'status' => $outcome?->status ?? '',
                    'score' => $outcome?->score ?? '',
                    'remarks' => $outcome?->remarks ?? '',
                ];
            })->filter()->values()->toArray();

            return;
        }

        if ($this->isHeat) {
            $this->heatResults = $entries->map(function ($entry) {
                $reg = $entry->competitionRegistration;
                if (! $reg) {
                    return null;
                }

                $heatResult = $reg->heatResults
                    ->firstWhere('competition_schedule_id', $this->schedule->id);

                return [
                    'heat_result_id' => $heatResult?->id,
                    'registration_id' => $reg->id,
                    'person_name' => $reg->participation?->person?->nama ?? '-',
                    'participant_number' => $reg->participation?->participant_number ?? '-',
                    'desa' => $reg->participation?->person?->desa?->desa_asal ?? '-',
                    'kelompok' => $reg->participation?->person?->kelompok?->kelompok_asal ?? '-',
                    'timeText' => CompetitionTime::format($heatResult?->score !== null ? (float) $heatResult->score : null),
                    'status' => $heatResult?->status ?? '',
                    'notes' => $heatResult?->notes ?? '',
                    'final_position' => $reg->outcome?->position ?? '',
                ];
            })->filter()->values()->toArray();

            return;
        }

        $this->outcomes = $entries->map(function ($entry) {
            $reg = $entry->competitionRegistration;
            if (! $reg) {
                return null;
            }

            return [
                'registration_id' => $reg->id,
                'person_name' => $reg->participation?->person?->nama ?? '-',
                'participant_number' => $reg->participation?->participant_number ?? '-',
                'desa' => $reg->participation?->person?->desa?->desa_asal ?? '-',
                'kelompok' => $reg->participation?->person?->kelompok?->kelompok_asal ?? '-',
                'position' => $reg->outcome?->position ?? '',
                'status' => $reg->outcome?->status ?? '',
                'score' => $reg->outcome?->score ?? '',
                'remarks' => $reg->outcome?->remarks ?? '',
            ];
        })->filter()->values()->toArray();
    }

    public function saveOutcomes(): void
    {
        Gate::authorize('manage-events');

        if ($this->isTeam) {
            $this->saveTeamOutcomes();

            return;
        }

        if ($this->isHeat) {
            $this->saveHeatResults();

            return;
        }

        $this->validate([
            'outcomes.*.position' => 'nullable|integer|min:0',
            'outcomes.*.status' => 'nullable|string|max:50',
            'outcomes.*.score' => 'nullable|numeric|min:0',
            'outcomes.*.remarks' => 'nullable|string|max:1000',
        ]);

        foreach ($this->outcomes as $data) {
            CompetitionOutcome::updateOrCreate(
                ['competition_registration_id' => $data['registration_id']],
                [
                    'position' => $data['position'] !== '' ? (int) $data['position'] : null,
                    'status' => $data['status'] ?: null,
                    'score' => $data['score'] !== '' ? (float) $data['score'] : null,
                    'remarks' => $data['remarks'] ?: null,
                ]
            );
        }

        session()->flash('success', 'Outcome berhasil disimpan.');
    }

    private function saveHeatResults(): void
    {
        $this->validate([
            'heatResults.*.timeText' => 'nullable|string|max:20',
            'heatResults.*.status' => 'nullable|string|max:50',
            'heatResults.*.notes' => 'nullable|string|max:1000',
        ]);

        foreach ($this->heatResults as $data) {
            $seconds = CompetitionTime::parse($data['timeText'] ?? null);

            CompetitionHeatResult::updateOrCreate(
                [
                    'competition_schedule_id' => $this->schedule->id,
                    'competition_registration_id' => $data['registration_id'],
                ],
                [
                    'score' => $seconds,
                    'status' => $data['status'] ?: null,
                    'notes' => $data['notes'] ?: null,
                ]
            );
        }

        session()->flash('success', 'Hasil heat berhasil disimpan.');
    }

    private function saveTeamOutcomes(): void
    {
        $this->validate([
            'teamOutcomes.*.position' => 'nullable|integer|min:0',
            'teamOutcomes.*.status' => 'nullable|string|max:50',
            'teamOutcomes.*.score' => 'nullable|numeric|min:0',
            'teamOutcomes.*.remarks' => 'nullable|string|max:1000',
        ]);

        foreach ($this->teamOutcomes as $data) {
            CompetitionTeamOutcome::updateOrCreate(
                ['competition_team_id' => $data['team_id']],
                [
                    'position' => $data['position'] !== '' ? (int) $data['position'] : null,
                    'status' => $data['status'] ?: null,
                    'score' => $data['score'] !== '' ? (float) $data['score'] : null,
                    'remarks' => $data['remarks'] ?: null,
                ]
            );
        }

        session()->flash('success', 'Hasil team berhasil disimpan.');
    }

    /**
     * Rank the schedule's participants automatically from their score (mass),
     * or rank the class's TEAMS (Team Mass).
     */
    public function autoRank(): void
    {
        Gate::authorize('manage-events');

        $event = app(ActiveEventContext::class)->requireCurrent();
        $service = app(CompetitionResultService::class);

        if ($this->isTeam) {
            $result = $service->rankTeams($event->id, $this->schedule->competition_class_id);

            if (! $result['ranked']) {
                session()->flash('error', 'Auto-ranking team tidak tersedia untuk format win/loss.');

                return;
            }

            session()->flash('success', 'Ranking team selesai: '.count($result['rows']).' team di-ranking ('.CompetitionResultType::label($result['result_type']).').');

            return;
        }

        $result = $service->rankSchedule($event->id, $this->schedule->id);

        if (! $result['ranked']) {
            session()->flash('error', 'Auto-ranking tidak tersedia untuk format win/loss.');

            return;
        }

        session()->flash('success', 'Ranking otomatis selesai: '.count($result['rows']).' peserta di-ranking ('.CompetitionResultType::label($result['result_type']).').');
    }

    /**
     * Aggregate all heats of the class into the final ranking + podium.
     */
    public function aggregateFinal(): void
    {
        Gate::authorize('manage-events');

        $event = app(ActiveEventContext::class)->requireCurrent();

        $result = app(CompetitionResultService::class)->aggregateHeatResults($event->id, $this->schedule->competition_class_id);

        if (! $result['ranked']) {
            session()->flash('error', 'Aggregasi final tidak tersedia untuk format win/loss.');

            return;
        }

        session()->flash('success', 'Final ranking berhasil dibuat: '.count($result['rows']).' peserta ('.CompetitionResultType::label($result['result_type']).').');
    }

    public function getResultTypeProperty(): string
    {
        return $this->schedule->competitionClass?->resultType() ?? CompetitionResultType::RANKING;
    }

    public function getResultDirectionProperty(): string
    {
        return CompetitionResultType::sortDirection($this->resultType);
    }

    public function getCanAutoRankProperty(): bool
    {
        $format = $this->schedule->competitionClass?->format;

        return CompetitionResultType::isRanked($this->resultType)
            && ! $this->isHeat
            && ! in_array($format, [CompetitionFormat::INDIVIDUAL_VS_INDIVIDUAL, CompetitionFormat::TEAM_VS_TEAM], true);
    }

    public function getPodiumProperty(): array
    {
        $event = app(ActiveEventContext::class)->requireCurrent();
        $service = app(CompetitionResultService::class);
        $format = $this->schedule->competitionClass?->format;

        if ($this->isTeam) {
            return $service->podiumForTeams($event->id, $this->schedule->competition_class_id);
        }

        // Heat & vs-format (bracket) memakai podium final kelas; mass memakai podium schedule.
        if ($this->isHeat || in_array($format, [CompetitionFormat::INDIVIDUAL_VS_INDIVIDUAL, CompetitionFormat::TEAM_VS_TEAM], true)) {
            return $service->podiumForClass($event->id, $this->schedule->competition_class_id);
        }

        return $service->podiumForSchedule($event->id, $this->schedule->id);
    }

    public function render()
    {
        $this->loadParticipants();

        return view('livewire.competition.schedule.outcome-manager', [
            'className' => $this->schedule->competitionClass?->name ?? '-',
            'categoryName' => $this->schedule->competitionClass?->competitionCategory?->name ?? '-',
            'venueName' => $this->schedule->venue?->name ?? '-',
            'resultType' => $this->resultType,
            'resultDirection' => $this->resultDirection,
            'canAutoRank' => $this->canAutoRank,
            'podium' => $this->podium,
            'isHeat' => $this->isHeat,
            'heatResults' => $this->heatResults,
            'isTeam' => $this->isTeam,
            'teamOutcomes' => $this->teamOutcomes,
        ]);
    }
}
