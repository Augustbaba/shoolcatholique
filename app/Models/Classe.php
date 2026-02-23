<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classe extends Model
{
    use HasFactory;

    protected $fillable = ['niveau_id', 'suffixe'];

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function classeAnnees()
    {
        return $this->hasMany(ClasseAnnee::class);
    }

    // Accesseur pour obtenir le nom complet de la classe (ex: "6ème A")
    public function getFullNameAttribute()
    {
        return $this->niveau->nom . ($this->suffixe ? ' ' . $this->suffixe : '');
    }
}