<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_listar_menu(): void
    {
        $usuario = User::factory()->create();
        MenuItem::factory()->create(['rotulo' => 'Artigos']);

        $this->actingAs($usuario)
            ->get(route('menu.index'))
            ->assertOk()
            ->assertSee('Artigos')
            ->assertSee(__('Novo item'));
    }

    public function test_usuario_pode_criar_item_simples_com_target_blank(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->post(route('menu.store'), [
            'rotulo' => 'Submissão',
            'url' => 'https://example.com/submit',
            'target_blank' => true,
            'ativo' => true,
            'links' => [],
        ]);

        $item = MenuItem::query()->firstOrFail();

        $response->assertRedirect(route('menu.edit', $item));
        $this->assertDatabaseHas('menu_itens', [
            'id' => $item->id,
            'rotulo' => 'Submissão',
            'url' => 'https://example.com/submit',
            'target_blank' => 1,
            'ativo' => 1,
            'parent_id' => null,
        ]);
    }

    public function test_usuario_pode_criar_item_com_links_inline(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->post(route('menu.store'), [
            'rotulo' => 'Sobre',
            'url' => null,
            'target_blank' => false,
            'ativo' => true,
            'links' => [
                [
                    'rotulo' => 'Equipe',
                    'url' => '/equipe',
                    'target_blank' => false,
                ],
                [
                    'rotulo' => 'Contato',
                    'url' => 'https://example.com/contato',
                    'target_blank' => true,
                ],
            ],
        ])->assertRedirect();

        $pai = MenuItem::query()->whereNull('parent_id')->where('rotulo', 'Sobre')->firstOrFail();

        $this->assertDatabaseHas('menu_itens', [
            'parent_id' => $pai->id,
            'rotulo' => 'Equipe',
            'url' => '/equipe',
            'ordem' => 1,
        ]);
        $this->assertDatabaseHas('menu_itens', [
            'parent_id' => $pai->id,
            'rotulo' => 'Contato',
            'url' => 'https://example.com/contato',
            'target_blank' => 1,
            'ordem' => 2,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Sobre')
            ->assertSee('Equipe')
            ->assertSee('Contato');
    }

    public function test_usuario_pode_atualizar_links_inline(): void
    {
        $usuario = User::factory()->create();
        $pai = MenuItem::factory()->create(['rotulo' => 'Sobre', 'url' => null]);
        $filho = MenuItem::factory()->filhoDe($pai)->create([
            'rotulo' => 'Equipe',
            'url' => '/equipe',
            'ordem' => 1,
        ]);

        $this->actingAs($usuario)->put(route('menu.update', $pai), [
            'rotulo' => 'Sobre nós',
            'url' => null,
            'target_blank' => false,
            'ativo' => true,
            'links' => [
                [
                    'id' => $filho->id,
                    'rotulo' => 'Nossa equipe',
                    'url' => '/nossa-equipe',
                    'target_blank' => true,
                ],
                [
                    'rotulo' => 'História',
                    'url' => '/historia',
                    'target_blank' => false,
                ],
            ],
        ])->assertRedirect(route('menu.edit', $pai));

        $this->assertDatabaseHas('menu_itens', [
            'id' => $filho->id,
            'rotulo' => 'Nossa equipe',
            'url' => '/nossa-equipe',
            'target_blank' => 1,
        ]);
        $this->assertDatabaseHas('menu_itens', [
            'parent_id' => $pai->id,
            'rotulo' => 'História',
            'url' => '/historia',
        ]);
        $this->assertSame(2, $pai->filhos()->count());
    }

    public function test_item_sem_links_exige_url(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('menu.store'), [
                'rotulo' => 'Sem link',
                'url' => null,
                'target_blank' => false,
                'ativo' => true,
                'links' => [],
            ])
            ->assertSessionHasErrors('url');
    }

    public function test_link_inline_exige_url(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('menu.store'), [
                'rotulo' => 'Pai',
                'url' => null,
                'target_blank' => false,
                'ativo' => true,
                'links' => [
                    [
                        'rotulo' => 'Filho',
                        'url' => '',
                        'target_blank' => false,
                    ],
                ],
            ])
            ->assertSessionHasErrors('links.0.url');
    }

    public function test_editar_filho_redireciona_para_o_pai(): void
    {
        $usuario = User::factory()->create();
        $pai = MenuItem::factory()->create(['rotulo' => 'Pai']);
        $filho = MenuItem::factory()->filhoDe($pai)->create(['rotulo' => 'Filho']);

        $this->actingAs($usuario)
            ->get(route('menu.edit', $filho))
            ->assertRedirect(route('menu.edit', $pai));
    }

    public function test_usuario_pode_reordenar_itens_de_topo(): void
    {
        $usuario = User::factory()->create();
        $primeiro = MenuItem::factory()->create(['rotulo' => 'A', 'ordem' => 1]);
        $segundo = MenuItem::factory()->create(['rotulo' => 'B', 'ordem' => 2]);

        $this->actingAs($usuario)
            ->post(route('menu.reorder'), [
                'ordem' => [$segundo->id, $primeiro->id],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(1, $segundo->fresh()->ordem);
        $this->assertSame(2, $primeiro->fresh()->ordem);
    }

    public function test_menu_ativo_aparece_no_site(): void
    {
        MenuItem::factory()->create([
            'rotulo' => 'Artigos públicos',
            'url' => '/#ultimos',
            'ativo' => true,
        ]);
        MenuItem::factory()->inativo()->create([
            'rotulo' => 'Item oculto',
            'url' => '/oculto',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Artigos públicos')
            ->assertDontSee('Item oculto');
    }

    public function test_usuario_pode_excluir_e_restaurar_item(): void
    {
        $usuario = User::factory()->create();
        $item = MenuItem::factory()->create(['rotulo' => 'Temporário']);

        $this->actingAs($usuario)
            ->delete(route('menu.destroy', $item))
            ->assertRedirect(route('menu.index'));

        $this->assertSoftDeleted($item);

        $this->actingAs($usuario)
            ->post(route('menu.restore', $item))
            ->assertRedirect(route('menu.index', ['status' => 'excluidos']));

        $this->assertNotSoftDeleted($item);
    }
}
