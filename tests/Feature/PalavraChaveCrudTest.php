<?php

namespace Tests\Feature;

use App\Models\Artigo;
use App\Models\PalavraChave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PalavraChaveCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_listar_palavras_chave(): void
    {
        $usuario = User::factory()->create();
        PalavraChave::factory()->count(2)->create();

        $this->actingAs($usuario)
            ->get(route('palavras_chave.index'))
            ->assertOk()
            ->assertSee(__('Nova palavra-chave'))
            ->assertDontSee(__('Slug'));
    }

    public function test_usuario_autenticado_pode_criar_palavra_chave(): void
    {
        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->post(route('palavras_chave.store'), [
            'nome' => 'Primeiro Amor',
        ]);

        $palavraChave = PalavraChave::query()->firstOrFail();

        $response->assertRedirect(route('palavras_chave.edit', $palavraChave));
        $this->assertDatabaseHas('palavras_chave', [
            'id' => $palavraChave->id,
            'nome' => 'Primeiro Amor',
        ]);
        $this->assertFalse(Schema::hasColumn('palavras_chave', 'slug'));
    }

    public function test_usuario_pode_atualizar_palavra_chave(): void
    {
        $usuario = User::factory()->create();
        $palavraChave = PalavraChave::factory()->create([
            'nome' => 'Antiga',
        ]);

        $this->actingAs($usuario)
            ->put(route('palavras_chave.update', $palavraChave), [
                'nome' => 'Nova',
            ])
            ->assertRedirect(route('palavras_chave.edit', $palavraChave));

        $this->assertDatabaseHas('palavras_chave', [
            'id' => $palavraChave->id,
            'nome' => 'Nova',
        ]);
    }

    public function test_usuario_pode_excluir_palavra_chave(): void
    {
        $usuario = User::factory()->create();
        $palavraChave = PalavraChave::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();
        $artigo->palavrasChave()->attach($palavraChave);

        $this->actingAs($usuario)
            ->delete(route('palavras_chave.destroy', $palavraChave))
            ->assertRedirect(route('palavras_chave.index'));

        $this->assertSoftDeleted($palavraChave);
        $this->assertDatabaseHas('artigo_palavra_chave', [
            'palavra_chave_id' => $palavraChave->id,
        ]);
    }

    public function test_usuario_pode_buscar_palavras_chave(): void
    {
        $usuario = User::factory()->create();
        PalavraChave::factory()->create(['nome' => 'Viagens']);
        PalavraChave::factory()->create(['nome' => 'Receitas']);

        $this->actingAs($usuario)
            ->get(route('palavras_chave.index', ['q' => 'Viagens']))
            ->assertOk()
            ->assertSee('Viagens')
            ->assertDontSee('Receitas');
    }
}
