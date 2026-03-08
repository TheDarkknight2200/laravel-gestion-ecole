<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle'        => 'required|string|max:255',
            'professeur'     => 'required|string|max:255',
            'volume_horaire' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required'        => 'Le libellé du cours est obligatoire.',
            'professeur.required'     => 'Le nom du professeur est obligatoire.',
            'volume_horaire.required' => 'Le volume horaire est obligatoire.',
            'volume_horaire.integer'  => 'Le volume horaire doit être un nombre entier.',
            'volume_horaire.min'      => 'Le volume horaire doit être supérieur à 0.',
        ];
    }
}