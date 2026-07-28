<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionBracketMatch extends Model
{
    protected $fillable = [
        'competition_bracket_id',
        'competition_schedule_id',
        'round',
        'position',
        'source_match_a_id',
        'source_match_b_id',
    ];

    protected function casts(): array
    {
        return [
            'round' => 'integer',
            'position' => 'integer',
        ];
    }

    public function bracket()
    {
        return $this->belongsTo(CompetitionBracket::class, 'competition_bracket_id');
    }

    public function schedule()
    {
        return $this->belongsTo(CompetitionSchedule::class, 'competition_schedule_id');
    }

    public function sourceMatchA()
    {
        return $this->belongsTo(CompetitionBracketMatch::class, 'source_match_a_id');
    }

    public function sourceMatchB()
    {
        return $this->belongsTo(CompetitionBracketMatch::class, 'source_match_b_id');
    }
}
