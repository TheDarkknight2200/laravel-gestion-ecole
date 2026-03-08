<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EtudiantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'prenom'         => $this->prenom,
            'nom'            => $this->nom,
            'email'          => $this->email,
            'date_naissance' => $this->date_naissance?->format('Y-m-d'),

            
            'cours' => $this->when(
                $request->query('include') === 'cours' && $this->relationLoaded('cours'),
                CoursResource::collection($this->cours)
            ),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}