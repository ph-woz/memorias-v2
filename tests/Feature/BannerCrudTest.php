<?php

namespace Tests\Feature;

use App\Enums\PosicaoHorizontal;
use App\Enums\PosicaoVertical;
use App\Models\Banner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BannerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_listar_banners(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        Banner::factory()->count(2)->create();

        $this->actingAs($usuario)
            ->get(route('banners.index'))
            ->assertOk()
            ->assertDontSee(__('Ver home'));
    }

    public function test_usuario_pode_filtrar_banners_por_status(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        Banner::factory()->create(['titulo' => 'Banner ligado', 'ativo' => true]);
        Banner::factory()->inativo()->create(['titulo' => 'Banner desligado']);

        $this->actingAs($usuario)
            ->get(route('banners.index', ['status' => 'ativos']))
            ->assertOk()
            ->assertSee('Banner ligado')
            ->assertDontSee('Banner desligado');

        $this->actingAs($usuario)
            ->get(route('banners.index', ['status' => 'desativados']))
            ->assertOk()
            ->assertSee('Banner desligado')
            ->assertDontSee('Banner ligado');
    }

    public function test_usuario_autenticado_pode_criar_banner(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $imagem = UploadedFile::fake()->image('banner.jpg', 1600, 700);

        $response = $this->actingAs($usuario)->post(route('banners.store'), [
            'titulo' => 'Banner principal',
            'slogan' => 'Uma frase de impacto',
            'imagem' => $imagem,
            'overlay_cor' => '#1a1a1a',
            'overlay_opacidade' => 60,
            'posicao_horizontal' => PosicaoHorizontal::Centro->value,
            'posicao_vertical' => PosicaoVertical::Centro->value,
            'cta_texto' => 'Ver mais',
            'cta_link' => 'https://example.com',
            'ativo' => true,
        ]);

        $banner = Banner::query()->firstOrFail();

        $response->assertRedirect(route('banners.edit', $banner));
        $this->assertDatabaseHas('banners', [
            'id' => $banner->id,
            'titulo' => 'Banner principal',
            'slogan' => 'Uma frase de impacto',
            'overlay_cor' => '#1a1a1a',
            'overlay_opacidade' => 60,
            'posicao_horizontal' => PosicaoHorizontal::Centro->value,
            'posicao_vertical' => PosicaoVertical::Centro->value,
            'cta_texto' => 'Ver mais',
            'ativo' => 1,
        ]);
        Storage::disk('public')->assertExists($banner->imagem);
    }

    public function test_usuario_pode_atualizar_banner(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $banner = Banner::factory()->create(['titulo' => 'Antigo']);

        $this->actingAs($usuario)
            ->put(route('banners.update', $banner), [
                'titulo' => 'Novo título',
                'slogan' => 'Novo slogan',
                'overlay_cor' => '#ff5500',
                'overlay_opacidade' => 25,
                'posicao_horizontal' => PosicaoHorizontal::Direita->value,
                'posicao_vertical' => PosicaoVertical::Topo->value,
                'cta_texto' => 'Começar',
                'cta_link' => 'https://example.com/comecar',
                'ativo' => false,
            ])
            ->assertRedirect(route('banners.edit', $banner));

        $bannerAtualizado = $banner->fresh();

        $this->assertSame('Novo título', $bannerAtualizado->titulo);
        $this->assertSame('Novo slogan', $bannerAtualizado->slogan);
        $this->assertSame('#ff5500', $bannerAtualizado->overlay_cor);
        $this->assertSame(25, $bannerAtualizado->overlay_opacidade);
        $this->assertSame(PosicaoHorizontal::Direita, $bannerAtualizado->posicao_horizontal);
        $this->assertSame(PosicaoVertical::Topo, $bannerAtualizado->posicao_vertical);
        $this->assertFalse($bannerAtualizado->ativo);
    }

    public function test_usuario_pode_reordenar_banners(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $primeiro = Banner::factory()->create(['ordem' => 1]);
        $segundo = Banner::factory()->create(['ordem' => 2]);

        $this->actingAs($usuario)
            ->postJson(route('banners.reorder'), [
                'ordem' => [$segundo->id, $primeiro->id],
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(1, $segundo->fresh()->ordem);
        $this->assertSame(2, $primeiro->fresh()->ordem);
    }

    public function test_usuario_pode_excluir_banner(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $banner = Banner::factory()->create();

        $this->actingAs($usuario)
            ->delete(route('banners.destroy', $banner))
            ->assertRedirect(route('banners.index'));

        $this->assertSoftDeleted($banner);
    }

    public function test_home_exibe_banners_ativos(): void
    {
        Storage::fake('public');

        Banner::factory()->create([
            'titulo' => 'Banner ativo',
            'ativo' => true,
            'ordem' => 1,
        ]);
        Banner::factory()->inativo()->create([
            'titulo' => 'Banner oculto',
            'ordem' => 2,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Banner ativo')
            ->assertDontSee('Banner oculto');
    }
}
