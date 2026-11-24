<?php

namespace Tests\Feature;

use App\Models\Artigo;
use App\Models\Etiqueta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtiquetaCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_listar_etiquetas(): void
    {
        $usuario = User::factory()->create();
        Etiqueta::factory()->count(2)->create();

        $this->actingAs($usuario)
            ->get(route('etiquetas.index'))
            ->assertOk()
            ->assertSee(__('Nova etiqueta'));
    }

    public function test_usuario_autenticado_pode_criar_etiqueta(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->post(route('etiquetas.store'), [
            'nome' => 'Destaque',
            'slug' => 'destaque',
            'cor' => '#EF4444',
        ]);

        $etiqueta = Etiqueta::query()->firstOrFail();

        $response->assertRedirect(route('etiquetas.edit', $etiqueta));
        $this->assertDatabaseHas('etiquetas', [
            'id' => $etiqueta->id,
            'nome' => 'Destaque',
            'slug' => 'destaque',
            'cor' => '#EF4444',
        ]);
    }

    public function test_usuario_pode_atualizar_etiqueta(): void
    {
        $usuario = User::factory()->create();
        $etiqueta = Etiqueta::factory()->create([
            'nome' => 'Antiga',
            'slug' => 'antiga',
            'cor' => '#6366F1',
        ]);

        $this->actingAs($usuario)
            ->put(route('etiquetas.update', $etiqueta), [
                'nome' => 'Nova',
                'slug' => 'nova',
                'cor' => '#10B981',
            ])
            ->assertRedirect(route('etiquetas.edit', $etiqueta));

        $this->assertDatabaseHas('etiquetas', [
            'id' => $etiqueta->id,
            'nome' => 'Nova',
            'slug' => 'nova',
            'cor' => '#10B981',
        ]);
    }

    public function test_usuario_pode_excluir_etiqueta(): void
    {
        $usuario = User::factory()->create();
        $etiqueta = Etiqueta::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();
        $artigo->etiquetas()->attach($etiqueta);

        $this->actingAs($usuario)
            ->delete(route('etiquetas.destroy', $etiqueta))
            ->assertRedirect(route('etiquetas.index'));

        $this->assertSoftDeleted($etiqueta);
        $this->assertDatabaseHas('artigo_etiqueta', [
            'etiqueta_id' => $etiqueta->id,
        ]);
    }

    public function test_usuario_pode_buscar_etiquetas(): void
    {
        $usuario = User::factory()->create();
        Etiqueta::factory()->create(['nome' => 'Destaque', 'slug' => 'destaque']);
        Etiqueta::factory()->create(['nome' => 'Arquivo', 'slug' => 'arquivo']);

        $this->actingAs($usuario)
            ->get(route('etiquetas.index', ['q' => 'destaque']))
            ->assertOk()
            ->assertSee('Destaque')
            ->assertDontSee('Arquivo');
    }

    public function test_usuario_pode_restaurar_etiqueta(): void
    {
        $usuario = User::factory()->create();
        $etiqueta = Etiqueta::factory()->create(['nome' => 'Restaurável']);
        $etiqueta->delete();

        $this->actingAs($usuario)
            ->post(route('etiquetas.restore', $etiqueta))
            ->assertRedirect(route('etiquetas.index', ['status' => 'excluidos']));

        $this->assertNotSoftDeleted($etiqueta);
    }

    public function test_nome_duplicado_recebe_erro_de_validacao(): void
    {
        $usuario = User::factory()->create();
        Etiqueta::factory()->create([
            'nome' => 'Destaque',
            'slug' => 'destaque',
        ]);

        $this->actingAs($usuario)->post(route('etiquetas.store'), [
            'nome' => 'Destaque',
            'slug' => 'destaque-2',
            'cor' => '#10B981',
        ])->assertSessionHasErrors('nome');
    }
}
