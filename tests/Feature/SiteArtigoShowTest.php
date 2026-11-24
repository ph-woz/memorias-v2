<?php

namespace Tests\Feature;

use App\Enums\StatusArtigo;
use App\Models\Artigo;
use App\Models\ArtigoDocumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteArtigoShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagina_do_artigo_renderiza_documentos_como_botoes(): void
    {
        $usuario = User::factory()->create();

        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Artigo com anexos',
            'slug' => 'aaaa',
            'doi' => '10.1590/0074-02760190230',
            'status' => StatusArtigo::Publicado,
            'publicado_em' => now()->subDay(),
        ]);

        ArtigoDocumento::factory()->for($artigo)->comArquivo('artigos/documentos/completo.pdf')->create([
            'titulo' => 'PDF completo',
            'ordem' => 0,
        ]);

        ArtigoDocumento::factory()->for($artigo)->create([
            'titulo' => 'HTML',
            'arquivo' => null,
            'url' => 'https://exemplo.com/artigo.html',
            'ordem' => 1,
        ]);

        $this->get(route('site.artigos.show', $artigo))
            ->assertOk()
            ->assertSeeText('Artigo com anexos')
            ->assertSeeText('10.1590/0074-02760190230')
            ->assertSeeText('PDF')
            ->assertSeeText('HTML')
            ->assertSee('artigos/documentos/completo.pdf', false)
            ->assertSee('https://exemplo.com/artigo.html', false);
    }

    public function test_pagina_do_artigo_vincula_citacoes_ao_painel_de_referencias(): void
    {
        $usuario = User::factory()->create();

        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Artigo com citações',
            'slug' => 'citacoes',
            'conteudo' => '<p>Resultado importante<sup>1, 2</sup> no estudo.</p>',
            'status' => StatusArtigo::Publicado,
            'publicado_em' => now()->subDay(),
        ]);

        $artigo->sincronizarReferencias([
            'Primeira referência completa.',
            'Segunda referência completa.',
        ]);

        $this->get(route('site.artigos.show', $artigo))
            ->assertOk()
            ->assertSee('class="citacao-ref"', false)
            ->assertSee('data-refs="1,2"', false)
            ->assertSee('artigoReferenciasPainel', false)
            ->assertSeeText('Primeira referência completa.')
            ->assertSeeText('Segunda referência completa.');
    }
}
