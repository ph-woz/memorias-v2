<?php

namespace Tests\Feature;

use App\Enums\StatusArtigo;
use App\Models\Artigo;
use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_convidado_nao_acessa_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_usuario_autenticado_ve_dashboard_com_indicadores(): void
    {
        $usuario = User::factory()->create();
        $categoria = Categoria::factory()->create(['nome' => 'Memórias']);

        $publicado = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'status' => StatusArtigo::Publicado,
            'publicado_em' => now()->subDay(),
            'titulo' => 'História publicada',
        ]);
        $publicado->categorias()->attach($categoria);

        Artigo::factory()->for($usuario, 'cadastrante')->create([
            'status' => StatusArtigo::Rascunho,
            'publicado_em' => null,
            'titulo' => 'Rascunho aberto',
        ]);

        $this->actingAs($usuario)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Painel editorial')
            ->assertSeeText($usuario->nome)
            ->assertSeeText('Período')
            ->assertSeeText('Hoje')
            ->assertSeeText('Últimos 7 dias')
            ->assertSeeText('Últimos 30 dias')
            ->assertSeeText('Este mês')
            ->assertSeeText('Mês passado')
            ->assertSeeText('Personalizado')
            ->assertSeeText('Publicados')
            ->assertSeeText('Rascunhos')
            ->assertSeeText('História publicada')
            ->assertSeeText('Rascunho aberto')
            ->assertSeeText('Precisa de atenção')
            ->assertSee('atencao=sem_foto', false)
            ->assertSee('atencao=sem_categoria', false)
            ->assertSee('atencao=rascunhos_antigos', false)
            ->assertSeeText('Por categoria')
            ->assertDontSeeText('Banners ativos')
            ->assertDontSeeText('Na home do site')
            ->assertDontSeeText('categorias ·');
    }

    public function test_filtro_de_atencao_lista_artigos_correspondentes(): void
    {
        $usuario = User::factory()->create();
        $categoria = Categoria::factory()->create();

        $semFoto = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Artigo sem foto',
            'foto' => null,
            'status' => StatusArtigo::Rascunho,
        ]);
        $semFoto->categorias()->attach($categoria);

        $comFoto = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Artigo com foto',
            'foto' => 'artigos/exemplo.webp',
            'status' => StatusArtigo::Rascunho,
        ]);
        $comFoto->categorias()->attach($categoria);

        $semCategoria = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Artigo órfão de categoria',
            'foto' => 'artigos/orfao.webp',
            'status' => StatusArtigo::Rascunho,
        ]);

        $this->actingAs($usuario)
            ->get(route('artigos.index', ['atencao' => 'sem_foto']))
            ->assertOk()
            ->assertSeeText('Artigos sem foto')
            ->assertSeeText('Artigo sem foto')
            ->assertDontSeeText('Artigo com foto');

        $this->actingAs($usuario)
            ->get(route('artigos.index', ['atencao' => 'sem_categoria']))
            ->assertOk()
            ->assertSeeText('Artigos sem categoria')
            ->assertSeeText('Artigo órfão de categoria')
            ->assertDontSeeText('Artigo sem foto');
    }

    public function test_filtro_de_periodo_limita_insights_do_dashboard(): void
    {
        $usuario = User::factory()->create();
        $categoriaMes = Categoria::factory()->create(['nome' => 'Categoria do mês']);
        $categoriaAntiga = Categoria::factory()->create(['nome' => 'Categoria antiga']);

        $artigoMes = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'status' => StatusArtigo::Publicado,
            'titulo' => 'Artigo deste mês',
            'publicado_em' => now(),
            'updated_at' => now(),
        ]);
        $artigoMes->categorias()->attach($categoriaMes);

        $artigoAntigo = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'status' => StatusArtigo::Publicado,
            'titulo' => 'Artigo antigo fora do período',
            'publicado_em' => now()->subMonths(3),
            'created_at' => now()->subMonths(3),
            'updated_at' => now()->subMonths(3),
        ]);
        $artigoAntigo->categorias()->attach($categoriaAntiga);

        $this->actingAs($usuario)
            ->get(route('dashboard', ['periodo' => 'este_mes']))
            ->assertOk()
            ->assertSeeText('Artigo deste mês')
            ->assertDontSeeText('Artigo antigo fora do período')
            ->assertSeeText('Categoria do mês')
            ->assertDontSeeText('Categoria antiga');

        $this->actingAs($usuario)
            ->get(route('dashboard', ['periodo' => 'hoje']))
            ->assertOk()
            ->assertSeeText('Hoje');
    }

    public function test_categoria_e_etiqueta_respeitam_publicacao_no_periodo(): void
    {
        $usuario = User::factory()->create();
        $categoria = Categoria::factory()->create(['nome' => 'Histórias de outubro']);
        $etiqueta = Etiqueta::factory()->create(['nome' => 'Outonal']);

        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'status' => StatusArtigo::Publicado,
            'titulo' => 'Publicado em outubro',
            'publicado_em' => now()->subMonthNoOverflow()->startOfMonth()->addDays(5),
            'created_at' => now()->subMonthNoOverflow()->startOfMonth()->addDays(5),
            'updated_at' => now(),
        ]);
        $artigo->categorias()->attach($categoria);
        $artigo->etiquetas()->sync([$etiqueta->id]);

        $this->actingAs($usuario)
            ->get(route('dashboard', ['periodo' => 'mes_passado']))
            ->assertOk()
            ->assertSeeText('Histórias de outubro')
            ->assertSeeText('Outonal');
    }
}
