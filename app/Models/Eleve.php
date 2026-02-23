<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Eleve extends Model
{
    use HasFactory;

    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'photo',
        'classe_annee_id',
        'parent_id', // parent principal
        'date_inscription',
        'statut',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_inscription' => 'date',
    ];

    /**
     * Relation avec la classe-année (la classe dans une année scolaire donnée)
     */
    public function classeAnnee()
    {
        return $this->belongsTo(ClasseAnnee::class);
    }

    /**
     * Parent principal (responsable principal)
     */
    public function parentPrincipal()
    {
        return $this->belongsTo(Parents::class, 'parent_id');
    }

    /**
     * Autres parents (via table pivot eleve_parent)
     */
    public function parents()
    {
        return $this->belongsToMany(Parents::class, 'eleve_parent')
                    ->withPivot('relation')
                    ->withTimestamps();
    }

    /**
     * Notes de l'élève
     */
    public function notes()
    {
        return $this->hasMany(Note::class);
    }

    /**
     * Paiements effectués pour cet élève
     */
    public function paiements()
    {
        return $this->hasMany(Paiement::class);
    }

    /**
     * Fichiers associés (photos, bulletins, etc.)
     */
    public function fichiers()
    {
        return $this->hasMany(Fichier::class);
    }

    /**
     * Scope pour filtrer par statut actif
     */
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    /**
     * Accesseur pour le nom complet
     */
    public function getFullNameAttribute()
    {
        return $this->prenom . ' ' . $this->nom;
    }
}