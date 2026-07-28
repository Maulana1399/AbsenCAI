<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompetitionCategory extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'code',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function competitionClasses()
    {
        return $this->hasMany(CompetitionClass::class);
    }
}
