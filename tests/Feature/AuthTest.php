<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_utilisateur_peut_sinscrire(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Admin Ecole',
            'email'                 => 'admin@ecole.sn',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['message', 'token', 'token_type']);
    }

    public function test_un_utilisateur_peut_se_connecter(): void
    {
        User::factory()->create([
            'email'    => 'admin@ecole.sn',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'admin@ecole.sn',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['message', 'token', 'token_type']);
    }

    public function test_acces_sans_token_retourne_401(): void
    {
        $this->getJson('/api/v1/auth/me')
             ->assertStatus(401);
    }

    public function test_utilisateur_connecte_peut_voir_son_profil(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->getJson('/api/v1/auth/me')
             ->assertStatus(200)
             ->assertJsonStructure(['data' => ['id', 'name', 'email']]);
    }

    public function test_utilisateur_connecte_peut_se_deconnecter(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
             ->postJson('/api/v1/auth/logout')
             ->assertStatus(200)
             ->assertJson(['message' => 'Déconnexion réussie']);
    }
}