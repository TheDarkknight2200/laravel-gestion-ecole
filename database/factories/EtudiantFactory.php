<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EtudiantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prenom'         => fake()->firstName(),
            'nom'            => fake()->lastName(),
            'email'          => fake()->unique()->safeEmail(),
            'date_naissance' => fake()->dateTimeBetween('-30 years', '-18 years')->format('Y-m-d'),
        ];
    }
}