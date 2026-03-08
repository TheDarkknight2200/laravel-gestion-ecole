<?php

namespace Tests\Feature;

use App\Models\Cours;
use App\Models\Etudiant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtudiantTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateurAuthentifie()
    {
        return User::factory()->create();
    }

    public function test_acces_sans_token_retourne_401(): void
    {
        $this->getJson('/api/v1/etudiants')
             ->assertStatus(401);
    }

    public function test_peut_lister_les_etudiants(): void
    {
        $user = $this->utilisateurAuthentifie();
        Etudiant::factory()->count(3)->create();

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/v1/etudiants')
             ->assertStatus(200)
             ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_peut_creer_un_etudiant(): void
    {
        $user = $this->utilisateurAuthentifie();

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/v1/etudiants', [
                 'prenom'         => 'Amadou',
                 'nom'            => 'Diallo',
                 'email'          => 'amadou@ecole.sn',
                 'date_naissance' => '2000-03-15',
             ])
             ->assertStatus(201)
             ->assertJsonPath('data.prenom', 'Amadou')
             ->assertJsonPath('data.email', 'amadou@ecole.sn');

        $this->assertDatabaseHas('etudiants', ['email' => 'amadou@ecole.sn']);
    }

    public function test_creation_etudiant_echoue_sans_email(): void
    {
        $user = $this->utilisateurAuthentifie();

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/v1/etudiants', [
                 'prenom'         => 'Amadou',
                 'nom'            => 'Diallo',
                 'date_naissance' => '2000-03-15',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
    }

    public function test_peut_afficher_un_etudiant(): void
    {
        $user     = $this->utilisateurAuthentifie();
        $etudiant = Etudiant::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->getJson("/api/v1/etudiants/{$etudiant->id}")
             ->assertStatus(200)
             ->assertJsonPath('data.id', $etudiant->id);
    }

    public function test_retourne_404_si_etudiant_introuvable(): void
    {
        $user = $this->utilisateurAuthentifie();

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/v1/etudiants/9999')
             ->assertStatus(404);
    }

    public function test_peut_modifier_un_etudiant(): void
    {
        $user     = $this->utilisateurAuthentifie();
        $etudiant = Etudiant::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->patchJson("/api/v1/etudiants/{$etudiant->id}", [
                 'prenom' => 'Nouveau Prénom',
             ])
             ->assertStatus(200)
             ->assertJsonPath('data.prenom', 'Nouveau Prénom');
    }

    public function test_peut_supprimer_un_etudiant(): void
    {
        $user     = $this->utilisateurAuthentifie();
        $etudiant = Etudiant::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->deleteJson("/api/v1/etudiants/{$etudiant->id}")
             ->assertStatus(204);

        $this->assertDatabaseMissing('etudiants', ['id' => $etudiant->id]);
    }

    public function test_peut_attacher_des_cours_a_un_etudiant(): void
    {
        $user     = $this->utilisateurAuthentifie();
        $etudiant = Etudiant::factory()->create();
        $cours    = Cours::factory()->count(2)->create();

        $this->actingAs($user, 'sanctum')
             ->postJson("/api/v1/etudiants/{$etudiant->id}/cours/attach", [
                 'cours_ids' => $cours->pluck('id')->toArray(),
             ])
             ->assertStatus(200)
             ->assertJsonStructure(['message', 'data']);

        $this->assertDatabaseHas('cours_etudiant', [
            'etudiant_id' => $etudiant->id,
            'cours_id'    => $cours->first()->id,
        ]);
    }

    public function test_peut_synchroniser_les_cours_dun_etudiant(): void
    {
        $user     = $this->utilisateurAuthentifie();
        $etudiant = Etudiant::factory()->create();
        $anciens  = Cours::factory()->count(2)->create();
        $nouveaux = Cours::factory()->count(2)->create();

        $etudiant->cours()->attach($anciens->pluck('id')->toArray());

        $this->actingAs($user, 'sanctum')
             ->postJson("/api/v1/etudiants/{$etudiant->id}/cours/sync", [
                 'cours_ids' => $nouveaux->pluck('id')->toArray(),
             ])
             ->assertStatus(200);

        $this->assertDatabaseMissing('cours_etudiant', [
            'etudiant_id' => $etudiant->id,
            'cours_id'    => $anciens->first()->id,
        ]);

        $this->assertDatabaseHas('cours_etudiant', [
            'etudiant_id' => $etudiant->id,
            'cours_id'    => $nouveaux->first()->id,
        ]);
    }
}