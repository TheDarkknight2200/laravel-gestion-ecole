<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CoursFactory extends Factory
{
    public function definition(): array
    {
        return [
            'libelle'        => fake()->randomElement([
                'Algorithmique', 'Base de données', 'Réseaux',
                'Mathématiques', 'Programmation Web', 'Systèmes d\'exploitation',
            ]),
            'professeur'     => fake()->name(),
            'volume_horaire' => fake()->numberBetween(20, 60),
        ];
    }
}