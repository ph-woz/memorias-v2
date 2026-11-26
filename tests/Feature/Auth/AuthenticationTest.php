<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_email(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'nickname' => 'user_nick',
        ]);

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertNotNull($user->fresh()->ultimo_acesso_em);
    }

    public function test_users_can_authenticate_using_nickname(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'nickname' => 'user_nick',
        ]);

        $response = $this->post('/login', [
            'login' => $user->nickname,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertNotNull($user->fresh()->ultimo_acesso_em);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_login_invalido_exibe_mensagem_traduzida_no_idioma_atual(): void
    {
        $user = User::factory()->create();
        $sessionKey = (string) config('localization.session_key');

        $casos = [
            'en_US' => 'These credentials do not match our records.',
            'es_ES' => 'Estas credenciales no coinciden con nuestros registros.',
            'pt_BR' => 'Estas credenciais não correspondem aos nossos registros.',
        ];

        foreach ($casos as $idioma => $mensagem) {
            $this->withSession([$sessionKey => $idioma])
                ->post('/login', [
                    'login' => $user->email,
                    'password' => 'wrong-password',
                ])
                ->assertSessionHasErrors([
                    'login' => $mensagem,
                ]);
        }
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
