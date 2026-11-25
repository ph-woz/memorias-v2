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

        $publicado = Artigo::factory()->publicado()->create([
            'titulo' => 'Artigo da categoria',
        ]);
        $publicado->categorias()->sync([$categoria->id]);

        $rascunho = Artigo::factory()->create([
            'titulo' => 'Rascunho oculto',
        ]);
        $rascunho->categorias()->sync([$categoria->id]);

        $this->get(route('site.categorias.show', $categoria))
            ->assertOk()
            ->assertSee('Memórias')
            ->assertSee('Artigo da categoria')
            ->assertDontSee('Rascunho oculto');
    }
}
