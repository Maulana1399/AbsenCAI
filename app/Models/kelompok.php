<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class kelompok extends Model
{
    protected $fillable = [
        'kelompok_asal',
        'desa_id',
    ];

    public function peserta() {
        return $this->hasMany(peserta::class);
    }

    public function desa() {
        return $this->belongsTo(desa::class);
    }
}

