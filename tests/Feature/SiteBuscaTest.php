<?php

namespace Tests\Feature;

use App\Enums\StatusArtigo;
use App\Models\Artigo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteBuscaTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_exibe_formulario_de_busca(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('name="q"', false)
            ->assertSee(route('site.busca', absolute: false), false);
    }

    public function test_pagina_de_busca_pede_termo_quando_vazia(): void
    {
        $this->get(route('site.busca'))
            ->assertOk()
            ->assertSeeText('Buscar artigos')
            ->assertSeeText('Digite um termo para encontrar artigos publicados.');
    }

    public function test_busca_retorna_artigos_publicados_correspondentes(): void
    {
        $usuario = User::factory()->create();

        Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Vetores da dengue no IOC',
            'status' => StatusArtigo::Publicado,
            'publicado_em' => now()->subDay(),
            'resumo' => 'Estudo de campo.',
        ]);

        Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'História da microbiologia',
            'status' => StatusArtigo::Publicado,
            'publicado_em' => now()->subDays(2),
            'resumo' => 'Outro tema.',
        ]);

        Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Vetores em rascunho',
            'status' => StatusArtigo::Rascunho,
            'publicado_em' => null,
        ]);

        $this->get(route('site.busca', ['q' => 'vetores']))
            ->assertOk()
            ->assertSeeText('Vetores da dengue no IOC')
            ->assertDontSeeText('História da microbiologia')
            ->assertDontSeeText('Vetores em rascunho');
    }

    public function test_busca_por_autor_encontra_artigo_publicado(): void
    {
        $usuario = User::factory()->create();

        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Artigo com autor especial',
            'status' => StatusArtigo::Publicado,
            'publicado_em' => now()->subDay(),
        ]);

        $artigo->sincronizarAutores(['Maria da Silva Cruz']);

        $this->get(route('site.busca', ['q' => 'Maria da Silva']))
            ->assertOk()
            ->assertSeeText('Artigo com autor especial');
    }

    public function test_usuario_autenticado_encontra_rascunhos_na_busca(): void
    {
        $usuario = User::factory()->create();

        Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Extracellular vesicles in draft search',
            'status' => StatusArtigo::Rascunho,
            'publicado_em' => null,
        ]);

        $this->actingAs($usuario)
            ->get(route('site.busca', ['q' => 'Extracellular']))
            ->assertOk()
            ->assertSeeText('Extracellular vesicles in draft search')
            ->assertSeeText('Rascunho')
            ->assertSeeText('incluindo rascunhos');
    }

    public function test_visitante_nao_encontra_rascunhos_na_busca(): void
    {
        $usuario = User::factory()->create();

        Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Extracellular vesicles in draft search',
            'status' => StatusArtigo::Rascunho,
            'publicado_em' => null,
        ]);

        $this->get(route('site.busca', ['q' => 'Extracellular']))
            ->assertOk()
            ->assertDontSeeText('Extracellular vesicles in draft search')
            ->assertSeeText('Nenhum artigo encontrado');
    }
}
