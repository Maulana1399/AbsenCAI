<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'code',
        'location_detail',
        'sort_order',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function rundownItems()
    {
        return $this->hasMany(RundownItem::class);
    }
}
