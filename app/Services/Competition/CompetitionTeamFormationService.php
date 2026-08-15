<?php

namespace App\Services\Competition;

use App\Models\CompetitionClass;
use App\Models\CompetitionTeam;
use App\Models\CompetitionTeamMember;
use App\Support\CompetitionFormat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Auto team formation for team-based competitions.
 *
 * Business rules:
 * - Only team formats (team_vs_team / team_mass) can be auto-formed.
 * - One Kelompok = one Team per CompetitionClass.
 * - Default team size = the smallest Kelompok in the class (players).
 * - Remaining participants of each Kelompok become substitutes.
 * - Participants are never moved between Kelompoks.
 * - Regu / PlacementService are NOT used for Competition Teams.
 */
class CompetitionTeamFormationService
{
    /**
     * @return array{
     *     class: CompetitionClass,
     *     team_size: int,
     *     teams: array<int, array{team: CompetitionTeam, kelompok: string, players: int, substitutes: int}>
     * }
     *
     * @throws \Illuminate\Validation\ValidationException bila team sudah dibentuk
     *                                                    dan `$force` (rebuild eksplisit) false.
     */
    public function formForClass(int $eventId, int $competitionClassId, ?int $forcedTeamSize = null, bool $force = false): array
    {
        return DB::transaction(function () use ($eventId, $competitionClassId, $forcedTeamSize, $force) {
            $class = CompetitionClass::where('event_id', $eventId)
                ->findOrFail($competitionClassId);

            if (! CompetitionFormat::isTeamFormat($class->format)) {
                throw ValidationException::withMessages([
                    'class' => 'Auto team formation hanya untuk format team (team_vs_team / team_mass).',
                ]);
            }

            $registrations = $class->competitionRegistrations()
                ->with('participation.person.kelompok')
                ->get()
                ->filter(fn ($registration) => $registration->participation?->person?->kelompok_id !== null)
                ->values();

            $byKelompok = $registrations->groupBy(fn ($registration) => $registration->participation->person->kelompok_id);

            $teamSize = $forcedTeamSize
                ?? $byKelompok->map(fn ($group) => $group->count())->min();

            if ($teamSize === null || $teamSize < 1) {
                throw ValidationException::withMessages([
                    'class' => 'Tidak ada peserta berkelompok untuk membentuk team.',
                ]);
            }

            // Guard: jangan regenerate bila team sudah dipakai di jadwal/hasil.
            $inUse = CompetitionTeam::where('competition_class_id', $class->id)
                ->get()
                ->contains(fn ($team) => $team->scheduleEntries()->exists() || $team->outcome()->exists());

            if ($inUse) {
                throw ValidationException::withMessages([
                    'class' => 'Tidak dapat membentuk ulang team: team sudah dipakai di jadwal/hasil.',
                ]);
            }

            // Manual protection: jangan diam-diam menimpa pembagian team yang sudah
            // ada (otomatis maupun manual). Rebuild hanya lewat aksi eksplisit ($force).
            $hasExisting = CompetitionTeam::where('competition_class_id', $class->id)
                ->whereHas('members')
                ->exists();

            if ($hasExisting && ! $force) {
                throw ValidationException::withMessages([
                    'class' => 'Tim sudah dibentuk. Membentuk ulang akan mengganti pembagian — gunakan rebuild eksplisit.',
                ]);
            }

            // Regenerate (transactional). Members cascade on team delete.
            CompetitionTeam::where('competition_class_id', $class->id)->get()->each->delete();

            $teams = [];

            foreach ($byKelompok as $kelompokId => $group) {
                $kelompok = $group->first()->participation->person->kelompok;

                $team = CompetitionTeam::create([
                    'event_id' => $eventId,
                    'competition_class_id' => $class->id,
                    'name' => $kelompok->kelompok_asal,
                    'kelompok_id' => $kelompok->id,
                    'is_active' => true,
                ]);

                $sorted = $group->sortBy('id')->values();
                $playerCount = min($sorted->count(), $teamSize);

                foreach ($sorted as $index => $registration) {
                    CompetitionTeamMember::create([
                        'competition_team_id' => $team->id,
                        'competition_registration_id' => $registration->id,
                        'is_substitute' => $index >= $playerCount,
                        'sort_order' => $index + 1,
                    ]);
                }

                $teams[] = [
                    'team' => $team,
                    'kelompok' => $kelompok->kelompok_asal,
                    'players' => $playerCount,
                    'substitutes' => $sorted->count() - $playerCount,
                ];
            }

            return [
                'class' => $class,
                'team_size' => $teamSize,
                'teams' => $teams,
            ];
        });
    }
}
