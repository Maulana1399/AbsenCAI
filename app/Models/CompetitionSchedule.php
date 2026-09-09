<?php

namespace App\Models;

use App\Support\CompetitionFormat;
use Illuminate\Database\Eloquent\Model;

class CompetitionSchedule extends Model
{
    protected $fillable = [
        'competition_class_id',
        'venue_id',
        'start_at',
        'end_at',
        'status',
        'required_participants',
        'winner_registration_id',
        'winner_team_id',
        'finish_reason',
        'finish_notes',
        'finished_at',
        'finished_by',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'required_participants' => 'integer',
            'finished_at' => 'datetime',
        ];
    }

    public function competitionClass()
    {
        return $this->belongsTo(CompetitionClass::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function scheduleEntries()
    {
        return $this->hasMany(CompetitionScheduleEntry::class);
    }

    public function winner()
    {
        return $this->belongsTo(CompetitionRegistration::class, 'winner_registration_id');
    }

    public function winnerTeam()
    {
        return $this->belongsTo(CompetitionTeam::class, 'winner_team_id');
    }

    public function finishedBy()
    {
        return $this->belongsTo(User::class, 'finished_by');
    }

    public function matchOfficials()
    {
        return $this->hasMany(CompetitionMatchOfficial::class, 'competition_schedule_id');
    }

    public function bracketMatch()
    {
        return $this->hasOne(CompetitionBracketMatch::class, 'competition_schedule_id');
    }

    public function heatResults()
    {
        return $this->hasMany(CompetitionHeatResult::class, 'competition_schedule_id');
    }

    /**
     * Minimum peserta agar pertandingan ini bisa dimainkan (Ready/Start).
     *
     * Pemilahan konsep:
     * - `required_participants` = KAPASITAS maksimum slot heat, bukan syarat start.
     * - Format Heat mengandung `min_participants_to_start` (default 2) sebagai
     *   source-of-truth syarat minimum untuk format heat (individual_heat /
     *   team_heat), per (class, round).
     * - Pertandingan non-heat (bracket / vs / mass) tetap memakai
     *   `required_participants` sebagai minimum — backward compatible (2/2 Ready,
     *   1/2 Scheduled dibiarkan utuh).
     */
    public function minParticipantsToStart(): int
    {
        if ($this->bracketMatch()->exists()) {
            return (int) $this->required_participants;
        }

        $format = $this->competitionClass?->format;

        if (in_array($format, [CompetitionFormat::INDIVIDUAL_HEAT, CompetitionFormat::TEAM_HEAT], true)) {
            $round = max(1, (int) intdiv((int) $this->sort_order, 100));
            $heatFormat = CompetitionHeatFormat::where('competition_class_id', $this->competition_class_id)
                ->where('round', $round)
                ->first();

            if ($heatFormat?->min_participants_to_start !== null) {
                return (int) $heatFormat->min_participants_to_start;
            }
        }

        return (int) $this->required_participants;
    }

    public function isReadyForStart(): bool
    {
        if ($this->status !== 'Ready') {
            return false;
        }

        return $this->scheduleEntries()->count() >= $this->minParticipantsToStart();
    }

    public function canAutoReady(): bool
    {
        if ($this->status !== 'Scheduled') {
            return false;
        }

        return $this->scheduleEntries()->count() >= $this->minParticipantsToStart();
    }

    public function scopeWherePlaying($query)
    {
        return $query->where('status', 'Playing');
    }

    public function scopeWhereReady($query)
    {
        return $query->where('status', 'Ready');
    }

    public function scopeWhereScheduled($query)
    {
        return $query->where('status', 'Scheduled');
    }

    public function scopeWhereFinished($query)
    {
        return $query->where('status', 'Finished');
    }

    public function scopeWhereWaitingResult($query)
    {
        return $query->where('status', 'Waiting Result');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $schedule) {
            $entryIds = $schedule->scheduleEntries()->pluck('competition_registration_id');

            CompetitionScheduleEntry::where('competition_schedule_id', $schedule->id)->delete();

            if ($entryIds->isNotEmpty()) {
                CompetitionOutcome::whereIn('competition_registration_id', $entryIds)->delete();
            }

            CompetitionBracketMatch::where('competition_schedule_id', $schedule->id)->delete();
        });
    }
}
