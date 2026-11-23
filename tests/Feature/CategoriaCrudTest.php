<?php

namespace Tests\Feature;

use App\Models\Artigo;
use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoriaCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_listar_categorias(): void
    {
        $usuario = User::factory()->create();
        Categoria::factory()->count(2)->create();

        $this->actingAs($usuario)
            ->get(route('categorias.index'))
            ->assertOk()
            ->assertSee(__('Nova categoria'));
    }

    public function test_usuario_autenticado_pode_criar_categoria_com_foto_e_seo(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $foto = UploadedFile::fake()->image('memorias.jpg');

        $response = $this->actingAs($usuario)->post(route('categorias.store'), [
            'nome' => 'Histórias de Família',
            'identificador' => 'historias-de-familia',
            'foto' => $foto,
            'seo_titulo' => 'Histórias de família para guardar',
            'seo_descricao' => 'Relatos e fotos que atravessam gerações.',
            'seo_palavras_chave' => 'família, histórias, memórias',
        ]);

        $categoria = Categoria::query()->firstOrFail();

        $response->assertRedirect(route('categorias.edit', $categoria));
        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'nome' => 'Histórias de Família',
            'identificador' => 'historias-de-familia',
            'seo_titulo' => 'Histórias de família para guardar',
            'seo_descricao' => 'Relatos e fotos que atravessam gerações.',
            'seo_palavras_chave' => 'família, histórias, memórias',
        ]);
        $this->assertNotNull($categoria->foto);
        Storage::disk('public')->assertExists($categoria->foto);
    }

    public function test_criacao_exige_foto(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)->post(route('categorias.store'), [
            'nome' => 'Sem Foto',
            'identificador' => 'sem-foto',
        ])->assertSessionHasErrors('foto');
    }

    public function test_slug_duplicado_recebe_sufixo_unico(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        Categoria::factory()->create([
            'nome' => 'Memórias',
            'identificador' => 'memorias',
        ]);

        $this->actingAs($usuario)->post(route('categorias.store'), [
            'nome' => 'Memórias',
            'identificador' => 'memorias',
            'foto' => UploadedFile::fake()->image('memorias.jpg'),
        ])->assertSessionHasErrors('identificador');
    }

    public function test_usuario_pode_atualizar_categoria(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $categoria = Categoria::factory()->create([
            'nome' => 'Antiga',
            'identificador' => 'antiga',
            'seo_titulo' => 'Antigo título',
        ]);

        $this->actingAs($usuario)
            ->put(route('categorias.update', $categoria), [
                'nome' => 'Nova',
                'identificador' => 'nova',
                'seo_titulo' => 'Novo título SEO',
                'seo_descricao' => 'Nova descrição SEO.',
                'seo_palavras_chave' => 'nova, categoria',
            ])
            ->assertRedirect(route('categorias.edit', $categoria));

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'nome' => 'Nova',
            'identificador' => 'nova',
            'seo_titulo' => 'Novo título SEO',
            'seo_descricao' => 'Nova descrição SEO.',
            'seo_palavras_chave' => 'nova, categoria',
        ]);
    }

    public function test_usuario_pode_excluir_categoria(): void
    {
        $usuario = User::factory()->create();
        $categoria = Categoria::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'criador')->create([
            'categoria_id' => $categoria->id,
        ]);

        $this->actingAs($usuario)
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'));

        $this->assertDatabaseMissing('categorias', ['id' => $categoria->id]);
        $this->assertNull($artigo->fresh()->categoria_id);
    }

    public function test_usuario_pode_buscar_categorias(): void
    {
        $usuario = User::factory()->create();
        Categoria::factory()->create(['nome' => 'Viagens', 'identificador' => 'viagens']);
        Categoria::factory()->create(['nome' => 'Receitas', 'identificador' => 'receitas']);

        $this->actingAs($usuario)
            ->get(route('categorias.index', ['q' => 'Viagens']))
            ->assertOk()
            ->assertSee('Viagens')
            ->assertDontSee('Receitas');
    }
}
