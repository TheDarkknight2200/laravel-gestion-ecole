<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEtudiantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

   
    public function rules(): array
    {
        $etudiantId = $this->route('etudiant');

        return [
            'prenom'         => 'sometimes|required|string|max:255',
            'nom'            => 'sometimes|required|string|max:255',
            'email'          => "sometimes|required|email|unique:etudiants,email,{$etudiantId}",
            'date_naissance' => 'sometimes|required|date|before:today',
        ];
    }

    public function messages(): array
    {
        return [
            'prenom.required'         => 'Le prénom est obligatoire.',
            'nom.required'            => 'Le nom est obligatoire.',
            'email.email'             => "L'email doit être une adresse email valide.",
            'email.unique'            => "Cet email est déjà utilisé par un autre étudiant.",
            'date_naissance.date'     => 'La date de naissance doit être une date valide.',
            'date_naissance.before'   => 'La date de naissance doit être antérieure à aujourd\'hui.',
        ];
    }
}