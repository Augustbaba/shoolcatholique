<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $fillable = [
        'eleve_id',
        'tranche_id',
        'montant',
        'date_paiement',
        'mode_paiement',
        'reference',
        'parent_id',
        'recu_path',
        'commentaire',
    ];



    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function tranche()
    {
        return $this->belongsTo(Tranche::class);
    }

    public function parent()
    {
        return $this->belongsTo(Parents::class, 'parent_id');
    }
}
