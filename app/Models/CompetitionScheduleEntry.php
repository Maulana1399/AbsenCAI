<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionScheduleEntry extends Model
{
    protected $fillable = [
        'competition_schedule_id',
        'competition_registration_id',
        'order_number',
        'lane',
        'corner',
        'position',
        'notes',
    ];

    public function competitionSchedule()
    {
        return $this->belongsTo(CompetitionSchedule::class);
    }

    public function competitionRegistration()
    {
        return $this->belongsTo(CompetitionRegistration::class);
    }
}
