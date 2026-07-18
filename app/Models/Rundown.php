<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rundown extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'rundown_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rundown_date' => 'date',
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function rundownItems()
    {
        return $this->hasMany(RundownItem::class);
    }
}
