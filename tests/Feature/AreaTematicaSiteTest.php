<?php

namespace Tests\Feature;

use App\Models\AreaTematica;
use App\Models\Artigo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AreaTematicaSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_pode_abrir_area_tematica_pelo_slug(): void
    {
        $areaTematica = AreaTematica::factory()->create([
            'nome' => 'Memória coletiva',
            'slug' => 'memoria-coletiva',
        ]);

        $artigo = Artigo::factory()->publicado()->create([
            'titulo' => 'Artigo da área',
        ]);
        $artigo->areasTematicas()->attach($areaTematica);

        Artigo::factory()->create([
            'titulo' => 'Rascunho oculto',
        ])->areasTematicas()->attach($areaTematica);

        $this->get(route('site.areas_tematicas.show', $areaTematica))
            ->assertOk()
            ->assertSee('Memória coletiva')
            ->assertSee('Artigo da área')
            ->assertDontSee('Rascunho oculto');

        $this->get('/area/memoria-coletiva')
            ->assertOk()
            ->assertSee('Memória coletiva');
    }
}
