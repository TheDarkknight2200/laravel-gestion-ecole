<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEtudiantRequest extends FormRequest
{
   
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
        return [
            'prenom'         => 'required|string|max:255',
            'nom'            => 'required|string|max:255',
            'email'          => 'required|email|unique:etudiants,email',
            'date_naissance' => 'required|date|before:today',
        ];
    }

    
    public function messages(): array
    {
        return [
            'prenom.required'         => 'Le prénom est obligatoire.',
            'nom.required'            => 'Le nom est obligatoire.',
            'email.required'          => "L'email est obligatoire.",
            'email.email'             => "L'email doit être une adresse email valide.",
            'email.unique'            => "Cet email est déjà utilisé par un autre étudiant.",
            'date_naissance.required' => 'La date de naissance est obligatoire.',
            'date_naissance.date'     => 'La date de naissance doit être une date valide.',
            'date_naissance.before'   => 'La date de naissance doit être antérieure à aujourd\'hui.',
        ];
    }
}