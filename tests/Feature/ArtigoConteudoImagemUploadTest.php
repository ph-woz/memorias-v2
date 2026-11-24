<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtigoConteudoImagemUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_convidado_nao_pode_enviar_imagem_do_conteudo(): void
    {
        Storage::fake('public');

        $this->postJson(route('artigos.conteudo.imagens.store'), [
            'imagem' => UploadedFile::fake()->image('figura.jpg'),
        ])->assertUnauthorized();
    }

    public function test_usuario_autenticado_pode_enviar_imagem_do_conteudo_como_webp(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();

        $response = $this->actingAs($usuario)->postJson(route('artigos.conteudo.imagens.store'), [
            'imagem' => UploadedFile::fake()->image('figura.jpg', 800, 600),
        ]);

        $response->assertOk()
            ->assertJsonStructure(['url']);

        $url = $response->json('url');
        $this->assertIsString($url);
        $this->assertStringContainsString('/storage/artigos/conteudo/', $url);
        $this->assertStringEndsWith('.webp', $url);

        $caminho = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH) ?? '');
        $caminho = ltrim($caminho, '/');

        Storage::disk('public')->assertExists($caminho);
        $this->assertSame('image/webp', Storage::disk('public')->mimeType($caminho));
    }

    public function test_upload_exige_arquivo_de_imagem(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson(route('artigos.conteudo.imagens.store'), [
                'imagem' => UploadedFile::fake()->create('documento.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['imagem']);
    }
}
