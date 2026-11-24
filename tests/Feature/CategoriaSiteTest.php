<?php

namespace Tests\Feature;

use App\Models\Artigo;
use App\Models\Categoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitante_pode_abrir_categoria_no_site(): void
    {
        $categoria = Categoria::factory()->create([
            'nome' => 'Memórias',
            'slug' => 'memorias',
        ]);

        Artigo::factory()->publicado()->create([
            'categoria_id' => $categoria->id,
            'titulo' => 'Artigo da categoria',
        ]);

        Artigo::factory()->create([
            'categoria_id' => $categoria->id,
            'titulo' => 'Rascunho oculto',
        ]);

        $this->get(route('site.categorias.show', $categoria))
            ->assertOk()
            ->assertSee('Memórias')
            ->assertSee('Artigo da categoria')
            ->assertDontSee('Rascunho oculto');
    }
}
