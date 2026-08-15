<?php

namespace App\Services\Competition;

use App\Models\CompetitionRegistration;
use App\Models\CompetitionTeam;
use App\Models\CompetitionTeamMember;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Team member management (PJ): add / remove / move player↔substitute / shuffle.
 *
 * All operations are event-scoped: the team's event must match the caller's
 * event context. Business validations enforced:
 * - member must be registered in the same CompetitionClass as the team,
 * - member must originate from the same Kelompok as the team,
 * - member must not already belong to another team of the same class.
 */
class CompetitionTeamService
{
    public function addMember(CompetitionTeam $team, int $registrationId, bool $asSubstitute = false): CompetitionTeamMember
    {
        $registration = CompetitionRegistration::with('participation.person')
            ->where('id', $registrationId)
            ->where('competition_class_id', $team->competition_class_id)
            ->first();

        if ($registration === null) {
            throw ValidationException::withMessages([
                'registration' => 'Peserta tidak terdaftar pada lomba/kelas team ini.',
            ]);
        }

        $personKelompokId = $registration->participation?->person?->kelompok_id;

        if ($team->kelompok_id !== null && (int) $personKelompokId !== (int) $team->kelompok_id) {
            throw ValidationException::withMessages([
                'registration' => 'Peserta harus berasal dari kelompok yang sama dengan team.',
            ]);
        }

        if ($team->members()->where('competition_registration_id', $registrationId)->exists()) {
            throw ValidationException::withMessages([
                'registration' => 'Peserta sudah menjadi anggota team ini.',
            ]);
        }

        $alreadyInOtherTeam = CompetitionTeamMember::where('competition_registration_id', $registrationId)
            ->whereHas('team', fn ($query) => $query
                ->where('competition_class_id', $team->competition_class_id)
                ->where('id', '!=', $team->id))
            ->exists();

        if ($alreadyInOtherTeam) {
            throw ValidationException::withMessages([
                'registration' => 'Peserta sudah berada di team lain pada lomba yang sama.',
            ]);
        }

        $maxOrder = $team->members()->max('sort_order') ?? 0;

        return CompetitionTeamMember::create([
            'competition_team_id' => $team->id,
            'competition_registration_id' => $registrationId,
            'is_substitute' => $asSubstitute,
            'sort_order' => $maxOrder + 1,
        ]);
    }

    public function removeMember(CompetitionTeam $team, int $memberId): void
    {
        $member = $team->members()->findOrFail($memberId);
        $member->delete();
    }

    public function setSubstitute(CompetitionTeam $team, int $memberId, bool $asSubstitute): CompetitionTeamMember
    {
        $member = $team->members()->findOrFail($memberId);

        $member->update(['is_substitute' => $asSubstitute]);

        return $member->fresh();
    }

    /**
     * Shuffle member order inside the team. Players and substitutes are
     * shuffled within their own pool (player↔substitute split is preserved).
     */
    public function shuffleMembers(CompetitionTeam $team): void
    {
        foreach ([false, true] as $isSubstitute) {
            $members = $team->members()
                ->where('is_substitute', $isSubstitute)
                ->orderBy('sort_order')
                ->get();

            foreach ($members->shuffle()->values() as $index => $member) {
                $member->update(['sort_order' => $index + 1]);
            }
        }
    }

    /**
     * @return Collection<int, CompetitionTeam>
     */
    public function listForClass(int $eventId, int $competitionClassId): Collection
    {
        return CompetitionTeam::where('event_id', $eventId)
            ->where('competition_class_id', $competitionClassId)
            ->with(['kelompok', 'players.competitionRegistration.participation.person', 'substitutes.competitionRegistration.participation.person'])
            ->orderBy('name')
            ->get();
    }
}
