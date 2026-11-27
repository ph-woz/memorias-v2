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

    public function test_edicao_mostra_ver_no_site(): void
    {
        $usuario = User::factory()->create();
        $areaTematica = AreaTematica::factory()->create([
            'nome' => 'Epidemiologia',
            'slug' => 'epidemiologia',
        ]);

        $this->actingAs($usuario)
            ->get(route('areas_tematicas.edit', $areaTematica))
            ->assertOk()
            ->assertSee('Ver no site')
            ->assertSee('value="ver_no_site"', false)
            ->assertSee('form="formulario-area-tematica"', false)
            ->assertSee("window.open('about:blank', 'memorias_ver_no_site')", false);
    }

    public function test_ver_no_site_salva_area_tematica_antes_de_abrir(): void
    {
        $usuario = User::factory()->create();
        $areaTematica = AreaTematica::factory()->create([
            'nome' => 'Antiga',
            'slug' => 'antiga',
        ]);

        $resposta = $this->actingAs($usuario)
            ->put(route('areas_tematicas.update', $areaTematica), [
                'nome' => 'Vetores',
                'slug' => 'vetores',
                'acao' => 'ver_no_site',
            ]);

        $areaAtualizada = $areaTematica->fresh();

        $resposta
            ->assertRedirect(route('areas_tematicas.edit', $areaAtualizada))
            ->assertSessionHas('status', 'area-tematica-atualizada')
            ->assertSessionHas(
                'abrir_site_url',
                route('site.areas_tematicas.show', $areaAtualizada),
            );

        $this->followRedirects($resposta)
            ->assertOk()
            ->assertSee('memorias_ver_no_site', false)
            ->assertSee('abrir-site-fallback', false)
            ->assertSee('vetores', false);
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
