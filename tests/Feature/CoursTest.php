<?php

namespace Tests\Feature;

use App\Models\Cours;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateurAuthentifie()
    {
        return User::factory()->create();
    }

    public function test_acces_sans_token_retourne_401(): void
    {
        $this->getJson('/api/v1/cours')
             ->assertStatus(401);
    }

    public function test_peut_lister_les_cours(): void
    {
        $user = $this->utilisateurAuthentifie();
        Cours::factory()->count(3)->create();

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/v1/cours')
             ->assertStatus(200)
             ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_peut_creer_un_cours(): void
    {
        $user = $this->utilisateurAuthentifie();

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/v1/cours', [
                 'libelle'        => 'Algorithmique',
                 'professeur'     => 'M. Sène',
                 'volume_horaire' => 40,
             ])
             ->assertStatus(201)
             ->assertJsonPath('data.libelle', 'Algorithmique');

        $this->assertDatabaseHas('cours', ['libelle' => 'Algorithmique']);
    }

    public function test_creation_cours_echoue_avec_volume_horaire_invalide(): void
    {
        $user = $this->utilisateurAuthentifie();

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/v1/cours', [
                 'libelle'        => 'Algorithmique',
                 'professeur'     => 'M. Sène',
                 'volume_horaire' => -5,
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['volume_horaire']);
    }

    public function test_peut_afficher_un_cours(): void
    {
        $user  = $this->utilisateurAuthentifie();
        $cours = Cours::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->getJson("/api/v1/cours/{$cours->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $cours->id);
    }

    public function test_peut_modifier_un_cours(): void
    {
        $user  = $this->utilisateurAuthentifie();
        $cours = Cours::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->patchJson("/api/v1/cours/{$cours->id}", [
                 'libelle' => 'Nouveau Libellé',
             ])
             ->assertStatus(200)
             ->assertJsonPath('data.libelle', 'Nouveau Libellé');
    }

    public function test_peut_supprimer_un_cours(): void
    {
        $user  = $this->utilisateurAuthentifie();
        $cours = Cours::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->deleteJson("/api/v1/cours/{$cours->id}")
             ->assertStatus(204);

        $this->assertDatabaseMissing('cours', ['id' => $cours->id]);
    }
}