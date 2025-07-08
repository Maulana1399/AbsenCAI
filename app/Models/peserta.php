<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class peserta extends Model
{
    protected $fillable = ['nama', 'nip', 'jenis_kelamin', 'kelompok_id', 'desa_id', 'regu_id'];
    
    public function kelompok() {
        return $this->belongsTo(kelompok::class);
    }

    public function desa() {
        return $this->belongsTo(desa::class);
    }

    public function regu() {
        return $this->belongsTo(regu::class);
    }
}

    