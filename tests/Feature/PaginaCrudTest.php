<?php

namespace Tests\Feature;

use App\Models\Pagina;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginaCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_listar_paginas(): void
    {
        $usuario = User::factory()->create();
        Pagina::factory()->create(['titulo' => 'Sobre nós']);

        $this->actingAs($usuario)
            ->get(route('paginas.index'))
            ->assertOk()
            ->assertSee('Sobre nós')
            ->assertSee(__('Nova página'));
    }

    public function test_usuario_pode_criar_pagina_com_url_personalizada_e_seo(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->post(route('paginas.store'), [
            'titulo' => 'Política editorial',
            'slug' => 'politica-editorial',
            'conteudo' => '<p>Conteúdo da política</p>',
            'ativo' => true,
            'seo_titulo' => 'Política editorial — Memórias',
            'seo_descricao' => 'Texto institucional',
            'seo_palavras_chave' => 'política, editorial, submissão',
        ]);

        $pagina = Pagina::query()->firstOrFail();

        $response->assertRedirect(route('paginas.edit', $pagina));
        $this->assertDatabaseHas('paginas', [
            'id' => $pagina->id,
            'titulo' => 'Política editorial',
            'slug' => 'politica-editorial',
            'ativo' => 1,
            'seo_titulo' => 'Política editorial — Memórias',
            'seo_descricao' => 'Texto institucional',
            'seo_palavras_chave' => 'política, editorial, submissão',
        ]);
    }

    public function test_usuario_pode_atualizar_pagina_com_seo(): void
    {
        $usuario = User::factory()->create();
        $pagina = Pagina::factory()->create([
            'titulo' => 'Antiga',
            'slug' => 'antiga',
            'seo_titulo' => 'Antigo título',
        ]);

        $this->actingAs($usuario)
            ->put(route('paginas.update', $pagina), [
                'titulo' => 'Nova política',
                'slug' => 'nova-politica',
                'conteudo' => '<p>Atualizado</p>',
                'ativo' => true,
                'seo_titulo' => 'Novo título SEO',
                'seo_descricao' => 'Nova descrição SEO.',
                'seo_palavras_chave' => 'nova, pagina',
            ])
            ->assertRedirect(route('paginas.edit', $pagina));

        $this->assertDatabaseHas('paginas', [
            'id' => $pagina->id,
            'titulo' => 'Nova política',
            'slug' => 'nova-politica',
            'seo_titulo' => 'Novo título SEO',
            'seo_descricao' => 'Nova descrição SEO.',
            'seo_palavras_chave' => 'nova, pagina',
        ]);
    }

    public function test_pagina_ativa_fica_disponivel_na_url_publica(): void
    {
        $pagina = Pagina::factory()->create([
            'titulo' => 'Submissão online',
            'slug' => 'submissao-online',
            'conteudo' => '<p>Envie seu manuscrito</p>',
            'ativo' => true,
            'seo_descricao' => 'Envie manuscritos ao IOC',
            'seo_palavras_chave' => 'submissão, manuscrito',
        ]);

        $this->get(route('site.paginas.show', $pagina))
            ->assertOk()
            ->assertSee('Submissão online')
            ->assertSee('Envie seu manuscrito', false)
            ->assertSee('name="description" content="Envie manuscritos ao IOC"', false)
            ->assertSee('name="keywords" content="submissão, manuscrito"', false);
    }

    public function test_pagina_inativa_nao_aparece_no_site(): void
    {
        $pagina = Pagina::factory()->inativa()->create([
            'slug' => 'rascunho-interno',
            'titulo' => 'Rascunho interno',
        ]);

        $this->get(route('site.paginas.show', $pagina))
            ->assertNotFound();
    }

    public function test_slug_reservado_nao_pode_ser_usado(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('paginas.store'), [
                'titulo' => 'Admin',
                'slug' => 'admin',
                'conteudo' => '<p>x</p>',
                'ativo' => true,
            ])
            ->assertSessionHasErrors('slug');
    }

    public function test_usuario_pode_excluir_e_restaurar_pagina(): void
    {
        $usuario = User::factory()->create();
        $pagina = Pagina::factory()->create(['titulo' => 'Temporária']);

        $this->actingAs($usuario)
            ->delete(route('paginas.destroy', $pagina))
            ->assertRedirect(route('paginas.index'));

        $this->assertSoftDeleted($pagina);

        $this->actingAs($usuario)
            ->post(route('paginas.restore', $pagina))
            ->assertRedirect(route('paginas.index', ['status' => 'excluidos']));

        $this->assertNotSoftDeleted($pagina);
    }
}
