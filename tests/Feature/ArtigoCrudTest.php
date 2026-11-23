<?php

namespace Tests\Feature;

use App\Enums\StatusArtigo;
use App\Models\Artigo;
use App\Models\Categoria;
use App\Models\PalavraChave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtigoCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_listar_artigos(): void
    {
        $usuario = User::factory()->create();
        Artigo::factory()->count(2)->for($usuario, 'criador')->create();

        $this->actingAs($usuario)
            ->get(route('artigos.index'))
            ->assertOk();
    }

    public function test_usuario_autenticado_pode_criar_artigo_com_varios_autores(): void
    {
        $usuario = User::factory()->create();
        $coautor = User::factory()->create();
        $categoria = Categoria::factory()->create();
        $palavrasChave = PalavraChave::factory()->count(2)->create();

        $response = $this->actingAs($usuario)->post(route('artigos.store'), [
            'titulo' => 'Minha primeira memória',
            'resumo' => 'Um resumo curto',
            'conteudo' => 'Conteúdo completo do artigo.',
            'status' => StatusArtigo::Publicado->value,
            'categoria_id' => $categoria->id,
            'autor_ids' => [$usuario->id, $coautor->id],
            'palavra_chave_ids' => $palavrasChave->pluck('id')->all(),
        ]);

        $artigo = Artigo::query()->firstOrFail();

        $response->assertRedirect(route('artigos.edit', $artigo));
        $this->assertDatabaseHas('artigos', [
            'id' => $artigo->id,
            'usuario_id' => $usuario->id,
            'titulo' => 'Minha primeira memória',
            'status' => StatusArtigo::Publicado->value,
        ]);
        $this->assertCount(2, $artigo->autores);
        $this->assertCount(2, $artigo->palavrasChave);
        $this->assertTrue($artigo->temAutor($usuario));
        $this->assertTrue($artigo->temAutor($coautor));
    }

    public function test_usuario_pode_atualizar_proprio_artigo(): void
    {
        $usuario = User::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'criador')->create([
            'titulo' => 'Título antigo',
        ]);

        $this->actingAs($usuario)
            ->put(route('artigos.update', $artigo), [
                'titulo' => 'Título novo',
                'resumo' => null,
                'conteudo' => 'Corpo atualizado',
                'status' => StatusArtigo::Rascunho->value,
                'categoria_id' => null,
                'autor_ids' => [$usuario->id],
                'palavra_chave_ids' => [],
            ])
            ->assertRedirect(route('artigos.edit', $artigo));

        $this->assertSame('Título novo', $artigo->fresh()->titulo);
    }

    public function test_coautor_pode_atualizar_artigo(): void
    {
        $criador = User::factory()->create();
        $coautor = User::factory()->create();
        $artigo = Artigo::factory()->for($criador, 'criador')->create();
        $artigo->autores()->sync([$criador->id, $coautor->id]);

        $this->actingAs($coautor)
            ->put(route('artigos.update', $artigo), [
                'titulo' => 'Editado pelo coautor',
                'conteudo' => 'Conteúdo',
                'status' => StatusArtigo::Rascunho->value,
                'autor_ids' => [$criador->id, $coautor->id],
            ])
            ->assertRedirect(route('artigos.edit', $artigo));

        $this->assertSame('Editado pelo coautor', $artigo->fresh()->titulo);
    }

    public function test_usuario_nao_pode_atualizar_artigo_de_outro(): void
    {
        $dono = User::factory()->create();
        $intruso = User::factory()->create();
        $artigo = Artigo::factory()->for($dono, 'criador')->create();

        $this->actingAs($intruso)
            ->put(route('artigos.update', $artigo), [
                'titulo' => 'Hack',
                'conteudo' => 'Hack',
                'status' => StatusArtigo::Rascunho->value,
                'autor_ids' => [$intruso->id],
            ])
            ->assertForbidden();
    }

    public function test_usuario_pode_excluir_proprio_artigo(): void
    {
        $usuario = User::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'criador')->create();

        $this->actingAs($usuario)
            ->delete(route('artigos.destroy', $artigo))
            ->assertRedirect(route('artigos.index'));

        $this->assertSoftDeleted($artigo);
    }
}
