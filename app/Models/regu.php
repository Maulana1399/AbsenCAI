<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class regu extends Model
{
    protected $fillable = ['regu'];
    protected $table = 'regus';

    public function peserta() {
        return $this->hasMany(peserta::class);
    }
}
