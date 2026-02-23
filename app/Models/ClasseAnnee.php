<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClasseAnnee extends Model
{
    use HasFactory;

    protected $fillable = [
        'classe_id',
        'annee_scolaire_id',
    ];

    /**
     * Relation avec la classe.
     */
    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    /**
     * Relation avec l'année scolaire.
     */
    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    /**
     * Relation many-to-many avec les matières via la table pivot classe_matieres.
     */
    public function matieres()
    {
        return $this->belongsToMany(Matiere::class, 'classe_matieres')
                    ->withPivot('coefficient')
                    ->withTimestamps();
    }

    /**
     * Relation avec les élèves.
     */
    public function eleves()
    {
        return $this->hasMany(Eleve::class);
    }
    public function scolarite()
{
    return $this->hasOne(Scolarite::class);
}
}