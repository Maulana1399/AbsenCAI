<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class desa extends Model
{
    protected $table = 'desas'; // <-- WAJIB
    protected $fillable = ['desa_asal'];

    public function kelompok() {
        return $this->hasMany(kelompok::class);
    }
}
