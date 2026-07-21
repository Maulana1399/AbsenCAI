<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $fillable = [
        'nama',
        'jenis_kelamin',
        'tanggal_lahir',
        'desa_id',
        'kelompok_id',
        'nip',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function desa()
    {
        return $this->belongsTo(desa::class);
    }

    public function kelompok()
    {
        return $this->belongsTo(kelompok::class);
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

    public function committeeAssignments()
    {
        return $this->hasMany(EventCommitteeAssignment::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
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
