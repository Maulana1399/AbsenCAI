<?php

namespace App\Models;

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

    public function isReadyForStart(): bool
    {
        if ($this->status !== 'Ready') {
            return false;
        }

        return $this->scheduleEntries()->count() >= $this->required_participants;
    }

    public function canAutoReady(): bool
    {
        if ($this->status !== 'Scheduled') {
            return false;
        }

        return $this->scheduleEntries()->count() >= $this->required_participants;
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
