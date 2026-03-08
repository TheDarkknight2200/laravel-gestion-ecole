<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CoursResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'libelle'        => $this->libelle,
            'professeur'     => $this->professeur,
            'volume_horaire' => $this->volume_horaire,

           
            'etudiants' => $this->when(
                $request->query('include') === 'etudiants' && $this->relationLoaded('etudiants'),
                EtudiantResource::collection($this->etudiants)
            ),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}