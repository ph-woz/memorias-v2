<?php

namespace Tests\Feature;

use App\Enums\StatusUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_listar_usuarios(): void
    {
        $admin = User::factory()->create();
        User::factory()->count(2)->create();

        $this->actingAs($admin)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee(__('Novo usuário'));
    }

    public function test_usuario_autenticado_pode_criar_usuario(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('usuarios.store'), [
            'nome' => 'Maria Silva',
            'nickname' => 'mariasilva',
            'email' => 'maria@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'email_verificado' => true,
            'status' => StatusUsuario::Ativo->value,
        ]);

        $usuario = User::query()->where('email', 'maria@example.com')->firstOrFail();

        $response->assertRedirect(route('usuarios.edit', $usuario));
        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'nome' => 'Maria Silva',
            'nickname' => 'mariasilva',
            'email' => 'maria@example.com',
            'status' => StatusUsuario::Ativo->value,
        ]);
        $this->assertNotNull($usuario->email_verified_at);
    }

    public function test_usuario_pode_atualizar_outro_usuario(): void
    {
        $admin = User::factory()->create();
        $usuario = User::factory()->create([
            'nome' => 'Antigo',
            'nickname' => 'antigo',
        ]);

        $this->actingAs($admin)
            ->put(route('usuarios.update', $usuario), [
                'nome' => 'Novo Nome',
                'nickname' => 'novonome',
                'email' => $usuario->email,
                'email_verificado' => true,
                'status' => StatusUsuario::Desativado->value,
            ])
            ->assertRedirect(route('usuarios.edit', $usuario));

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'nome' => 'Novo Nome',
            'nickname' => 'novonome',
            'status' => StatusUsuario::Desativado->value,
        ]);
    }

    public function test_usuario_pode_filtrar_por_status(): void
    {
        $admin = User::factory()->create(['nome' => 'Admin']);
        User::factory()->create(['nome' => 'Usuario Ativo', 'status' => StatusUsuario::Ativo]);
        User::factory()->desativado()->create(['nome' => 'Usuario Desativado']);

        $this->actingAs($admin)
            ->get(route('usuarios.index', ['status' => 'ativos']))
            ->assertOk()
            ->assertSee('Usuario Ativo')
            ->assertDontSee('Usuario Desativado');

        $this->actingAs($admin)
            ->get(route('usuarios.index', ['status' => 'desativados']))
            ->assertOk()
            ->assertSee('Usuario Desativado')
            ->assertDontSee('Usuario Ativo');
    }

    public function test_usuario_pode_excluir_outro_usuario(): void
    {
        $admin = User::factory()->create();
        $usuario = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $usuario))
            ->assertRedirect(route('usuarios.index'));

        $this->assertDatabaseMissing('users', ['id' => $usuario->id]);
    }

    public function test_usuario_nao_pode_excluir_a_si_mesmo(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $admin))
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_usuario_pode_buscar_usuarios(): void
    {
        $admin = User::factory()->create(['nome' => 'Admin']);
        User::factory()->create(['nome' => 'Ana Costa', 'nickname' => 'anacosta']);
        User::factory()->create(['nome' => 'Bruno Lima', 'nickname' => 'brunolima']);

        $this->actingAs($admin)
            ->get(route('usuarios.index', ['q' => 'Ana']))
            ->assertOk()
            ->assertSee('Ana Costa')
            ->assertDontSee('Bruno Lima');
    }
}
