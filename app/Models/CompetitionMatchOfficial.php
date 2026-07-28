<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionMatchOfficial extends Model
{
    protected $fillable = [
        'competition_schedule_id',
        'user_id',
        'role',
    ];

    public function schedule()
    {
        return $this->belongsTo(CompetitionSchedule::class, 'competition_schedule_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
