<?php

namespace Tests\Feature;

use App\Models\AreaTematica;
use App\Models\Artigo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaTematicaCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_listar_areas_tematicas(): void
    {
        $usuario = User::factory()->create();
        AreaTematica::factory()->count(2)->create();

        $this->actingAs($usuario)
            ->get(route('areas_tematicas.index'))
            ->assertOk()
            ->assertSee(__('Nova área temática'));
    }

    public function test_usuario_autenticado_pode_criar_area_tematica(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->post(route('areas_tematicas.store'), [
            'nome' => 'Memória coletiva',
            'slug' => 'memoria-coletiva',
        ]);

        $areaTematica = AreaTematica::query()->firstOrFail();

        $response->assertRedirect(route('areas_tematicas.edit', $areaTematica));
        $this->assertDatabaseHas('areas_tematicas', [
            'id' => $areaTematica->id,
            'nome' => 'Memória coletiva',
            'slug' => 'memoria-coletiva',
        ]);
    }

    public function test_usuario_pode_atualizar_area_tematica(): void
    {
        $usuario = User::factory()->create();
        $areaTematica = AreaTematica::factory()->create([
            'nome' => 'Antiga',
            'slug' => 'antiga',
        ]);

        $this->actingAs($usuario)
            ->put(route('areas_tematicas.update', $areaTematica), [
                'nome' => 'Nova',
                'slug' => 'nova',
            ])
            ->assertRedirect(route('areas_tematicas.edit', $areaTematica));

        $this->assertDatabaseHas('areas_tematicas', [
            'id' => $areaTematica->id,
            'nome' => 'Nova',
            'slug' => 'nova',
        ]);
    }

    public function test_usuario_pode_excluir_area_tematica(): void
    {
        $usuario = User::factory()->create();
        $areaTematica = AreaTematica::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();
        $artigo->areasTematicas()->attach($areaTematica);

        $this->actingAs($usuario)
            ->delete(route('areas_tematicas.destroy', $areaTematica))
            ->assertRedirect(route('areas_tematicas.index'));

        $this->assertSoftDeleted($areaTematica);
        $this->assertDatabaseHas('artigo_area_tematica', [
            'area_tematica_id' => $areaTematica->id,
        ]);
    }

    public function test_usuario_pode_buscar_areas_tematicas(): void
    {
        $usuario = User::factory()->create();
        AreaTematica::factory()->create(['nome' => 'História', 'slug' => 'historia']);
        AreaTematica::factory()->create(['nome' => 'Educação', 'slug' => 'educacao']);

        $this->actingAs($usuario)
            ->get(route('areas_tematicas.index', ['q' => 'historia']))
            ->assertOk()
            ->assertSee('História')
            ->assertDontSee('Educação');
    }

    public function test_usuario_pode_restaurar_area_tematica(): void
    {
        $usuario = User::factory()->create();
        $areaTematica = AreaTematica::factory()->create(['nome' => 'Restaurável']);
        $areaTematica->delete();

        $this->actingAs($usuario)
            ->post(route('areas_tematicas.restore', $areaTematica))
            ->assertRedirect(route('areas_tematicas.index', ['status' => 'excluidos']));

        $this->assertNotSoftDeleted($areaTematica);
    }
}
