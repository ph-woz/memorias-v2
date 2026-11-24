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
            'slug' => 'historias-de-familia',
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
            'slug' => 'historias-de-familia',
            'seo_titulo' => 'Histórias de família para guardar',
            'seo_descricao' => 'Relatos e fotos que atravessam gerações.',
            'seo_palavras_chave' => 'família, histórias, memórias',
        ]);
        $this->assertNotNull($categoria->foto);
        $this->assertStringEndsWith('.webp', $categoria->foto);
        Storage::disk('public')->assertExists($categoria->foto);
        $this->assertSame('image/webp', Storage::disk('public')->mimeType($categoria->foto));
    }

    public function test_usuario_pode_criar_categoria_sem_foto(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->post(route('categorias.store'), [
            'nome' => 'Sem Foto',
            'slug' => 'sem-foto',
        ]);

        $categoria = Categoria::query()->where('slug', 'sem-foto')->firstOrFail();

        $response->assertRedirect(route('categorias.edit', $categoria));
        $this->assertNull($categoria->foto);
    }

    public function test_slug_duplicado_recebe_erro_de_validacao(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        Categoria::factory()->create([
            'nome' => 'Memórias',
            'slug' => 'memorias',
        ]);

        $this->actingAs($usuario)->post(route('categorias.store'), [
            'nome' => 'Outro nome',
            'slug' => 'memorias',
            'foto' => UploadedFile::fake()->image('memorias.jpg'),
        ])->assertSessionHasErrors('slug');
    }

    public function test_nome_duplicado_recebe_erro_de_validacao(): void
    {
        $usuario = User::factory()->create();
        Categoria::factory()->create([
            'nome' => 'Memórias',
            'slug' => 'memorias',
        ]);

        $this->actingAs($usuario)->post(route('categorias.store'), [
            'nome' => 'Memórias',
            'slug' => 'memorias-2',
        ])->assertSessionHasErrors('nome');
    }

    public function test_usuario_pode_atualizar_categoria(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $categoria = Categoria::factory()->create([
            'nome' => 'Antiga',
            'slug' => 'antiga',
            'seo_titulo' => 'Antigo título',
        ]);

        $this->actingAs($usuario)
            ->put(route('categorias.update', $categoria), [
                'nome' => 'Nova',
                'slug' => 'nova',
                'seo_titulo' => 'Novo título SEO',
                'seo_descricao' => 'Nova descrição SEO.',
                'seo_palavras_chave' => 'nova, categoria',
            ])
            ->assertRedirect(route('categorias.edit', $categoria));

        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'nome' => 'Nova',
            'slug' => 'nova',
            'seo_titulo' => 'Novo título SEO',
            'seo_descricao' => 'Nova descrição SEO.',
            'seo_palavras_chave' => 'nova, categoria',
        ]);
    }

    public function test_usuario_pode_excluir_categoria(): void
    {
        $usuario = User::factory()->create();
        $categoria = Categoria::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();
        $artigo->categorias()->attach($categoria);

        $this->actingAs($usuario)
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'));

        $this->assertSoftDeleted($categoria);
        $this->assertDatabaseHas('artigo_categoria', [
            'artigo_id' => $artigo->id,
            'categoria_id' => $categoria->id,
        ]);
    }

    public function test_usuario_pode_listar_e_restaurar_categoria_excluida(): void
    {
        $usuario = User::factory()->create();
        $categoria = Categoria::factory()->create(['nome' => 'Arquivada']);
        $categoria->delete();

        $this->actingAs($usuario)
            ->get(route('categorias.index', ['status' => 'excluidos']))
            ->assertOk()
            ->assertSee('Arquivada');

        $this->actingAs($usuario)
            ->post(route('categorias.restore', $categoria))
            ->assertRedirect(route('categorias.index', ['status' => 'excluidos']));

        $this->assertNotSoftDeleted($categoria);
    }

    public function test_usuario_pode_buscar_categorias(): void
    {
        $usuario = User::factory()->create();
        Categoria::factory()->create(['nome' => 'Viagens', 'slug' => 'viagens']);
        Categoria::factory()->create(['nome' => 'Receitas', 'slug' => 'receitas']);

        $this->actingAs($usuario)
            ->get(route('categorias.index', ['q' => 'Viagens']))
            ->assertOk()
            ->assertSee('Viagens')
            ->assertDontSee('Receitas');
    }
}
