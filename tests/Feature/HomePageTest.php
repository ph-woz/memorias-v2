<?php

namespace Tests\Feature;

use App\Enums\StatusArtigo;
use App\Models\Artigo;
use App\Models\Banner;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_exibe_artigos_publicados_e_categorias(): void
    {
        $usuario = User::factory()->create();
        $categoria = Categoria::factory()->create(['nome' => 'Histórias']);

        Banner::factory()->create([
            'titulo' => 'Banner principal',
            'ativo' => true,
            'ordem' => 1,
        ]);

        $publicado = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Memória na varanda',
            'status' => StatusArtigo::Publicado,
            'publicado_em' => now()->subDay(),
            'resumo' => 'Um resumo para a home.',
        ]);
        $publicado->categorias()->attach($categoria);

        Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Rascunho oculto',
            'status' => StatusArtigo::Rascunho,
            'publicado_em' => null,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeText('Últimas publicações')
            ->assertSeeText('Memória na varanda')
            ->assertDontSeeText('Rascunho oculto')
            ->assertSeeText('Categorias')
            ->assertSeeText('Histórias')
            ->assertSeeText('Banner principal');
    }
}
