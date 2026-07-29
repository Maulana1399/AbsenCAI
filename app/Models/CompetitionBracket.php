<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionBracket extends Model
{
    protected $fillable = [
        'competition_class_id',
        'name',
        'participant_count',
        'status',
    ];

    public function competitionClass()
    {
        return $this->belongsTo(CompetitionClass::class);
    }

    public function bracketMatches()
    {
        return $this->hasMany(CompetitionBracketMatch::class, 'competition_bracket_id');
    }

    public function schedules()
    {
        return $this->hasManyThrough(CompetitionSchedule::class, CompetitionBracketMatch::class, 'competition_bracket_id', 'id', 'id', 'competition_schedule_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (self $bracket) {
            $scheduleIds = $bracket->bracketMatches()->pluck('competition_schedule_id');

            $bracket->bracketMatches()->delete();

            CompetitionSchedule::whereIn('id', $scheduleIds)->each(function ($schedule) {
                $schedule->delete();
            });
        });
    }
}
