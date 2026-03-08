<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Etudiant extends Model
{
    use HasFactory;

   
    protected $table = 'etudiants';

   
    protected $fillable = [
        'prenom',
        'nom',
        'email',
        'date_naissance',
    ];

    
    protected $casts = [
        'date_naissance' => 'date',
    ];

    
    public function cours()
    {
        return $this->belongsToMany(
            Cours::class,       
            'cours_etudiant',   
            'etudiant_id',      
            'cours_id'          
        );
    }
}