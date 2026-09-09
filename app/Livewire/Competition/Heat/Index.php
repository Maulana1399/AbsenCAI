<?php

namespace App\Livewire\Competition\Heat;

use App\Models\CompetitionClass;
use App\Services\Competition\CompetitionHeatManagerService;
use App\Services\Competition\CompetitionMultiRoundHeatService;
use App\Support\ActiveEventContext;
use App\Support\CompetitionResultType;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class Index extends Component
{
    public string $selectedClassId = '';

    public bool $showFormatForm = false;

    public int $formatRound = 1;

    public int $formatParticipants = 7;

    public int $formatMinParticipants = 2;

    public int $formatQualifiers = 3;

    public function mount(): void
    {
        app(ActiveEventContext::class)->requireCurrent();

        $this->selectedClassId = (string) ($this->heatClasses()->first()?->id ?? '');
    }

    public function selectClass($classId): void
    {
        $this->selectedClassId = (string) $classId;
        $this->reset(['showFormatForm', 'formatRound', 'formatParticipants', 'formatMinParticipants', 'formatQualifiers']);
        $this->formatRound = 1;
        $this->formatParticipants = 7;
        $this->formatMinParticipants = 2;
        $this->formatQualifiers = 3;
        $this->resetErrorBag();
    }

    public function toggleFormatForm(): void
    {
        $this->showFormatForm = ! $this->showFormatForm;
        $this->reset(['formatRound', 'formatParticipants', 'formatMinParticipants', 'formatQualifiers']);
        $this->formatRound = 1;
        $this->formatParticipants = 7;
        $this->formatMinParticipants = 2;
        $this->formatQualifiers = 3;
        $this->resetErrorBag();
    }

    public function createFormat(): void
    {
        Gate::authorize('manage-events');

        $event = app(ActiveEventContext::class)->requireCurrent();

        $this->validate([
            'formatRound' => 'required|integer|min:1',
            'formatParticipants' => 'required|integer|min:1|max:99',
            'formatMinParticipants' => 'required|integer|min:1|max:99',
            'formatQualifiers' => 'required|integer|min:1|max:99',
        ]);

        $this->validateFormat($this->formatParticipants, $this->formatMinParticipants, $this->formatQualifiers);

        $service = app(CompetitionHeatManagerService::class);

        $service->upsertFormat(
            $event->id,
            (int) $this->selectedClassId,
            $this->formatRound,
            $this->formatParticipants,
            $this->formatQualifiers,
            $this->formatMinParticipants,
        );

        $this->showFormatForm = false;
        session()->flash('success', "Format heat Round {$this->formatRound} disimpan.");
        $this->reset(['formatRound', 'formatParticipants', 'formatMinParticipants', 'formatQualifiers']);
        $this->formatRound = 1;
        $this->formatParticipants = 7;
        $this->formatMinParticipants = 2;
        $this->formatQualifiers = 3;
    }

    public function deleteFormat(int $formatId): void
    {
        Gate::authorize('manage-events');

        $event = app(ActiveEventContext::class)->requireCurrent();

        app(CompetitionHeatManagerService::class)->deleteFormat($event->id, $formatId);

        session()->flash('success', 'Format heat dihapus.');
    }

    public function generateRound(int $round): void
    {
        Gate::authorize('manage-events');

        $event = app(ActiveEventContext::class)->requireCurrent();
        $service = app(CompetitionHeatManagerService::class);

        $result = $service->generateRound($event->id, (int) $this->selectedClassId, $round);

        if (! $result['generated']) {
            session()->flash('error', $this->generateMessage($result['reason'] ?? 'unknown', $round));

            return;
        }

        session()->flash('success', "Round {$round} dibuat: {$result['heat_count']} heat, {$result['competitors_used']} kompetitor dipasang ke heat.");
    }

    public function advanceRound(int $round): void
    {
        Gate::authorize('manage-events');

        $event = app(ActiveEventContext::class)->requireCurrent();
        $service = app(CompetitionHeatManagerService::class);

        $result = $service->generateNextRound($event->id, (int) $this->selectedClassId, $round);

        if (! $result['advanced']) {
            session()->flash('error', $this->advanceMessage($result['reason'] ?? 'unknown', $round, $result['next_round'] ?? $round + 1));

            return;
        }

        session()->flash('success', "Advancement Round {$result['round']} → Round {$result['next_round']}: {$result['qualifiers']} lolos, {$result['heat_count']} heat baru dibuat, {$result['assigned']} dijadwalkan.");
    }

    public function removeRound(int $round): void
    {
        Gate::authorize('manage-events');

        $event = app(ActiveEventContext::class)->requireCurrent();
        $service = app(CompetitionHeatManagerService::class);

        $result = $service->removeRoundSchedules($event->id, (int) $this->selectedClassId, $round);

        if (! $result['deleted']) {
            session()->flash('error', $this->removeMessage($result['reason'] ?? 'unknown', $round));

            return;
        }

        session()->flash('success', "Round {$result['round']} heat dihapus (".count($result['schedule_ids']).' heat).');
    }

    public function rebuildRound(int $round): void
    {
        Gate::authorize('manage-events');

        $event = app(ActiveEventContext::class)->requireCurrent();
        $service = app(CompetitionHeatManagerService::class);

        $result = $service->rebuildRound($event->id, (int) $this->selectedClassId, $round);

        if (! $result['rebuilt']) {
            session()->flash('error', $this->rebuildMessage($result['reason'] ?? 'unknown', $round));

            return;
        }

        session()->flash('success', "Round {$round} dibangun ulang dari format: {$result['heat_count']} heat, {$result['competitors_used']} kompetitor dipasang.");
    }

    private function validateFormat(int $participants, int $minParticipants, int $qualifiers): void
    {
        if ($minParticipants > $participants) {
            $this->addError('formatMinParticipants', 'Minimum peserta untuk start tidak boleh melebihi peserta per heat.');
        }

        if ($qualifiers > $participants) {
            $this->addError('formatQualifiers', 'Jumlah lolos tidak boleh melebihi peserta per heat.');
        }
    }

    private function generateMessage(string $reason, int $round): string
    {
        return match ($reason) {
            'no_format' => 'Belum ada format untuk Round '.$round.'. Buat format terlebih dahulu.',
            'round_exists' => 'Round '.$round.' sudah punya heat. Hapus heat round tersebut dulu bila ingin generate ulang.',
            'no_competitors' => 'Belum ada peserta/team terdaftar di kelas ini.',
            default => 'Generate heat gagal ('.$reason.').',
        };
    }

    private function advanceMessage(string $reason, int $round, int $nextRound): string
    {
        return match ($reason) {
            'no_format' => 'Belum ada format untuk Round '.$round.'.',
            'no_next_format' => 'Belum ada format untuk Round '.$nextRound.' sehingga round berikutnya TIDAK dibuat. Tambahkan format Round '.$nextRound.' bila memang kompetisi berlanjut.',
            'qualified_pool_insufficient' => 'Qualifier belum cukup untuk membangun Round '.$nextRound.'. Tunggu heat lain selesai & di-qualify hingga pool mencapai kapasitas format.',
            'next_round_exists' => 'Round '.$nextRound.' sudah punya heat. Hapus heat round '.$nextRound.' dulu bila ingin generate ulang.',
            'no_qualifiers' => 'Tidak ada qualifier dari Round '.$round.'.',
            default => 'Advancement gagal ('.$reason.').',
        };
    }

    private function removeMessage(string $reason, int $round): string
    {
        return match ($reason) {
            'no_heats' => 'Round '.$round.' tidak punya heat.',
            'round_started' => 'Round '.$round.' sudah dimulai (Playing/Finished) sehingga heat tidak bisa dihapus.',
            default => 'Hapus heat gagal ('.$reason.').',
        };
    }

    private function rebuildMessage(string $reason, int $round): string
    {
        return match ($reason) {
            'no_format' => 'Belum ada format untuk Round '.$round.'.',
            'round_started' => 'Round '.$round.' sudah dimulai (Playing/Finished) sehingga tidak bisa dibangun ulang.',
            'has_results' => 'Round '.$round.' sudah punya hasil yang diinput; hapus hasil heat dulu sebelum membangun ulang.',
            'no_competitors' => 'Belum ada peserta/team terdaftar di kelas ini.',
            default => 'Build ulang gagal ('.$reason.').',
        };
    }

    public function render()
    {
        $event = app(ActiveEventContext::class)->current();
        $service = app(CompetitionHeatManagerService::class);
        $multiRound = app(CompetitionMultiRoundHeatService::class);

        $classes = $this->heatClasses();
        $selected = $classes->firstWhere('id', (int) $this->selectedClassId);

        $formats = collect();
        $rounds = collect();
        $poolCount = 0;

        if ($selected) {
            $poolCount = $service->competitorCount($selected->id);
            $formats = $service->formats($selected->id);

            $rounds = $formats->map(function ($format) use ($service, $multiRound, $selected) {
                $schedules = $multiRound->roundSchedules($selected->id, $format->round);

                return [
                    'round' => $format->round,
                    'format' => $format,
                    'estimated_heat_count' => $service->computeHeatCount($selected->id, $format->round),
                    'needs_rebuild' => $schedules->isNotEmpty()
                        && $schedules->contains(fn ($schedule) => (int) $schedule->required_participants !== (int) $format->participants_per_heat),
                    'schedules' => $schedules->map(function ($schedule) use ($selected) {
                        return $this->scheduleCard($schedule, $selected);
                    })->values(),
                ];
            })->values();
        }

        return view('livewire.competition.heat.index', [
            'classes' => $classes,
            'selected' => $selected,
            'formats' => $formats,
            'rounds' => $rounds,
            'poolCount' => $poolCount,
            'resultTypeLabel' => $selected ? CompetitionResultType::label($selected->resultType()) : '-',
            'resultDirection' => $selected ? $this->directionLabel($selected->resultType()) : '-',
        ]);
    }

    private function heatClasses()
    {
        $event = app(ActiveEventContext::class)->current();
        $service = app(CompetitionHeatManagerService::class);

        return CompetitionClass::with('competitionCategory')
            ->where('event_id', $event?->id)
            ->where('is_active', true)
            ->whereIn('format', $service::SUPPORTED_FORMATS)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function directionLabel(string $resultType): string
    {
        return CompetitionResultType::sortDirection($resultType) === 'asc'
            ? 'Terkecil menang (tercepat ranking terbaik)'
            : 'Terbesar menang (skor tertinggi ranking terbaik)';
    }

    private function scheduleCard($schedule, CompetitionClass $class): array
    {
        $isTeam = $class->isTeamFormat();

        $heatResults = \App\Models\CompetitionHeatResult::where('competition_schedule_id', $schedule->id)
            ->get()
            ->keyBy($isTeam ? 'competition_team_id' : 'competition_registration_id');

        $query = $schedule->scheduleEntries()
            ->with($isTeam ? 'team' : 'competitionRegistration.participation.person')
            ->orderBy('order_number')
            ->orderBy('id');

        $entries = $query->get()->map(function ($entry) use ($isTeam, $heatResults) {
            if ($isTeam) {
                $team = $entry->team;
                $name = $team?->name ?? '-';
                $number = 'Team';
                $heatResult = $heatResults->get($entry->competition_team_id);
            } else {
                $reg = $entry->competitionRegistration;
                $name = $reg?->participation?->person?->nama ?? '-';
                $number = $reg?->participation?->participant_number ?? '-';
                $heatResult = $heatResults->get($entry->competition_registration_id);
            }

            return [
                'name' => $name,
                'number' => $number,
                'position' => $heatResult?->position,
                'status' => $heatResult?->status,
            ];
        })->values();

        return [
            'id' => $schedule->id,
            'sort_order' => $schedule->sort_order,
            'status' => $schedule->status,
            'required_participants' => $schedule->required_participants,
            'participants_count' => $entries->count(),
            'entries' => $entries,
        ];
    }
}
