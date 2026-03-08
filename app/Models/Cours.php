<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cours extends Model
{
    use HasFactory;

    
    protected $table = 'cours';

    
    protected $fillable = [
        'libelle',
        'professeur',
        'volume_horaire',
    ];

   
    protected $casts = [
        'volume_horaire' => 'integer',
    ];


     public function getRouteKeyName(): string
      {
        return 'id';
      }

    
    public function etudiants()
    {
        return $this->belongsToMany(
            Etudiant::class,    
            'cours_etudiant',   
            'cours_id',         
            'etudiant_id'       
        );
    }
}