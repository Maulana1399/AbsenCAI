<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $fillable = [
        'nama',
        'jenis_kelamin',
        'desa_id',
        'nip',
    ];

    public function desa()
    {
        return $this->belongsTo(desa::class);
    }

    public function participations()
    {
        return $this->hasMany(Participation::class);
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'participations');
    }

    public function legacyPesertaMapping()
    {
        return $this->hasOne(LegacyPesertaMapping::class, 'person_id');
    }

    public function getJenisKelaminLabelAttribute(): string
    {
        return match ($this->jenis_kelamin) {
            'L' => 'Laki - Laki',
            'P' => 'Perempuan',
            default => '',
        };
    }
}
