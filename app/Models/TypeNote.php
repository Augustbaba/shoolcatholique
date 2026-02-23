<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeNote extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'code', 'max_par_periode'];

    public function notes()
    {
        return $this->hasMany(Note::class);
    }
}