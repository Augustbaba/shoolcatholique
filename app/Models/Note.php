<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'eleve_id',
        'matiere_id',
        'periode_id',
        'type_note_id',
        'enseignant_id',
        'valeur',
        'commentaire',
    ];

    protected $casts = [
        'valeur' => 'decimal:1',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class);
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }

    public function typeNote()
    {
        return $this->belongsTo(TypeNote::class);
    }

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }
}