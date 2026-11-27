<?php

namespace Tests\Feature;

use App\Enums\StatusArtigo;
use App\Models\AreaTematica;
use App\Models\Artigo;
use App\Models\Categoria;
use App\Models\Etiqueta;
use App\Models\PalavraChave;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtigoCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_autenticado_pode_listar_artigos(): void
    {
        $usuario = User::factory()->create();
        Artigo::factory()->count(2)->for($usuario, 'cadastrante')->create();

        $this->actingAs($usuario)
            ->get(route('artigos.index'))
            ->assertOk();
    }

    public function test_usuario_autenticado_pode_criar_artigo_com_varios_autores(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $categoria = Categoria::factory()->create();
        $palavrasChave = PalavraChave::factory()->count(2)->create();
        $foto = UploadedFile::fake()->image('memoria.jpg');

        $response = $this->actingAs($usuario)->post(route('artigos.store'), [
            'titulo' => 'Minha primeira memória',
            'slug' => 'minha-primeira-memoria',
            'doi' => '10.1000/memoria.2024.001',
            'resumo' => 'Um resumo curto',
            'conteudo' => 'Conteúdo completo do artigo.',
            'endereco_instituicoes' => "Universidade X\nRua das Flores, 100",
            'status' => StatusArtigo::Publicado->value,
            'categorias' => [$categoria->id],
            'autores' => ['Ana Souza', 'Bruno Lima'],
            'palavras_chave' => $palavrasChave->pluck('nome')->all(),
            'seo_titulo' => 'Memória SEO',
            'seo_descricao' => 'Descrição SEO do artigo.',
            'seo_palavras_chave' => 'memória, família',
            'foto' => $foto,
        ]);

        $artigo = Artigo::query()->firstOrFail();

        $response->assertRedirect(route('artigos.edit', $artigo));
        $this->assertDatabaseHas('artigos', [
            'id' => $artigo->id,
            'cadastrante_id' => $usuario->id,
            'titulo' => 'Minha primeira memória',
            'slug' => 'minha-primeira-memoria',
            'doi' => '10.1000/memoria.2024.001',
            'endereco_instituicoes' => "Universidade X\nRua das Flores, 100",
            'seo_titulo' => 'Memória SEO',
            'seo_descricao' => 'Descrição SEO do artigo.',
            'seo_palavras_chave' => 'memória, família',
            'status' => StatusArtigo::Publicado->value,
        ]);
        $this->assertDatabaseHas('artigo_categoria', [
            'artigo_id' => $artigo->id,
            'categoria_id' => $categoria->id,
        ]);
        $this->assertNotNull($artigo->foto);
        $this->assertStringEndsWith('.webp', $artigo->foto);
        Storage::disk('public')->assertExists($artigo->foto);
        $this->assertSame('image/webp', Storage::disk('public')->mimeType($artigo->foto));
        $this->assertCount(2, $artigo->autores);
        $this->assertSame(['Ana Souza', 'Bruno Lima'], $artigo->autores->pluck('nome')->all());
        $this->assertSame([0, 1], $artigo->autores->pluck('ordem')->all());
        $this->assertCount(2, $artigo->palavrasChave);
    }

    public function test_usuario_pode_reordenar_autores_do_artigo(): void
    {
        $usuario = User::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();
        $artigo->autores()->delete();
        $artigo->autores()->createMany([
            ['nome' => 'Primeiro', 'ordem' => 0],
            ['nome' => 'Segundo', 'ordem' => 1],
        ]);

        $this->actingAs($usuario)
            ->put(route('artigos.update', $artigo), [
                'titulo' => $artigo->titulo,
                'slug' => $artigo->slug,
                'resumo' => $artigo->resumo,
                'conteudo' => $artigo->conteudo,
                'status' => StatusArtigo::Rascunho->value,
                'autores' => ['Segundo', 'Primeiro', 'Terceiro'],
                'palavras_chave' => [],
            ])
            ->assertRedirect(route('artigos.edit', $artigo));

        $this->assertSame(
            ['Segundo', 'Primeiro', 'Terceiro'],
            $artigo->fresh()->autores->pluck('nome')->all(),
        );
    }

    public function test_usuario_pode_associar_varias_categorias_ao_artigo(): void
    {
        $usuario = User::factory()->create();
        $primeira = Categoria::factory()->create(['nome' => 'Histórias']);
        $segunda = Categoria::factory()->create(['nome' => 'Notícias']);
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();

        $this->actingAs($usuario)
            ->put(route('artigos.update', $artigo), [
                'titulo' => $artigo->titulo,
                'slug' => $artigo->slug,
                'resumo' => $artigo->resumo,
                'conteudo' => $artigo->conteudo,
                'status' => StatusArtigo::Rascunho->value,
                'categorias' => [$primeira->id, $segunda->id],
                'autores' => [$usuario->nome],
            ])
            ->assertRedirect(route('artigos.edit', $artigo));

        $artigo->refresh()->load('categorias');

        $this->assertCount(2, $artigo->categorias);
        $this->assertTrue($artigo->categorias->contains('id', $primeira->id));
        $this->assertTrue($artigo->categorias->contains('id', $segunda->id));
    }

    public function test_usuario_pode_associar_e_criar_palavras_chave_pelo_artigo(): void
    {
        $usuario = User::factory()->create();
        $existente = PalavraChave::factory()->create(['nome' => 'Infância']);
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();

        $this->actingAs($usuario)
            ->put(route('artigos.update', $artigo), [
                'titulo' => $artigo->titulo,
                'slug' => $artigo->slug,
                'resumo' => $artigo->resumo,
                'conteudo' => $artigo->conteudo,
                'status' => StatusArtigo::Rascunho->value,
                'autores' => [$usuario->nome],
                'palavras_chave' => ['Infância', 'Nova Memória'],
            ])
            ->assertRedirect(route('artigos.edit', $artigo));

        $artigo->refresh()->load('palavrasChave');

        $this->assertCount(2, $artigo->palavrasChave);
        $this->assertTrue($artigo->palavrasChave->contains('id', $existente->id));
        $this->assertDatabaseHas('palavras_chave', ['nome' => 'Nova Memória']);
        $this->assertSame(2, PalavraChave::query()->count());
    }

    public function test_usuario_pode_associar_e_criar_areas_tematicas_pelo_artigo(): void
    {
        $usuario = User::factory()->create();
        $existente = AreaTematica::factory()->create(['nome' => 'História']);
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();

        $this->actingAs($usuario)
            ->put(route('artigos.update', $artigo), [
                'titulo' => $artigo->titulo,
                'slug' => $artigo->slug,
                'resumo' => $artigo->resumo,
                'conteudo' => $artigo->conteudo,
                'status' => StatusArtigo::Rascunho->value,
                'autores' => [$usuario->nome],
                'areas_tematicas' => ['História', 'Memória coletiva'],
            ])
            ->assertRedirect(route('artigos.edit', $artigo));

        $artigo->refresh()->load('areasTematicas');

        $this->assertCount(2, $artigo->areasTematicas);
        $this->assertTrue($artigo->areasTematicas->contains('id', $existente->id));
        $this->assertDatabaseHas('areas_tematicas', ['nome' => 'Memória coletiva']);
        $this->assertSame(2, AreaTematica::query()->count());
    }

    public function test_usuario_pode_associar_e_criar_etiquetas_pelo_artigo(): void
    {
        $usuario = User::factory()->create();
        $existente = Etiqueta::factory()->create(['nome' => 'Destaque', 'slug' => 'destaque']);
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();

        $this->actingAs($usuario)
            ->put(route('artigos.update', $artigo), [
                'titulo' => $artigo->titulo,
                'slug' => $artigo->slug,
                'resumo' => $artigo->resumo,
                'conteudo' => $artigo->conteudo,
                'status' => StatusArtigo::Rascunho->value,
                'autores' => [$usuario->nome],
                'etiquetas' => ['Destaque', 'Nova etiqueta'],
            ])
            ->assertRedirect(route('artigos.edit', $artigo));

        $artigo->refresh()->load('etiquetas');

        $this->assertCount(2, $artigo->etiquetas);
        $this->assertTrue($artigo->etiquetas->contains('id', $existente->id));
        $this->assertDatabaseHas('etiquetas', [
            'nome' => 'Nova etiqueta',
            'cor' => Etiqueta::COR_PADRAO,
        ]);
        $this->assertSame(2, Etiqueta::query()->count());
    }

    public function test_usuario_pode_filtrar_artigos_por_etiqueta(): void
    {
        $usuario = User::factory()->create();
        $destaque = Etiqueta::factory()->create(['nome' => 'Destaque', 'slug' => 'destaque']);
        $arquivo = Etiqueta::factory()->create(['nome' => 'Arquivo', 'slug' => 'arquivo']);
        $entrevista = Etiqueta::factory()->create(['nome' => 'Entrevista', 'slug' => 'entrevista']);

        $comDestaque = Artigo::factory()->for($usuario, 'cadastrante')->create(['titulo' => 'Com destaque']);
        $comArquivo = Artigo::factory()->for($usuario, 'cadastrante')->create(['titulo' => 'Com arquivo']);
        $comEntrevista = Artigo::factory()->for($usuario, 'cadastrante')->create(['titulo' => 'Com entrevista']);

        $comDestaque->etiquetas()->attach($destaque);
        $comArquivo->etiquetas()->attach($arquivo);
        $comEntrevista->etiquetas()->attach($entrevista);

        $this->actingAs($usuario)
            ->get(route('artigos.index', ['etiquetas' => [$destaque->id, $arquivo->id]]))
            ->assertOk()
            ->assertSee('Com destaque')
            ->assertSee('Com arquivo')
            ->assertDontSee('Com entrevista');
    }

    public function test_usuario_pode_filtrar_artigos_por_categoria_e_area_tematica(): void
    {
        $usuario = User::factory()->create();
        $categoria = Categoria::factory()->create(['nome' => 'Memórias']);
        $outraCategoria = Categoria::factory()->create(['nome' => 'Outra']);
        $terceiraCategoria = Categoria::factory()->create(['nome' => 'Arquivo']);
        $area = AreaTematica::factory()->create(['nome' => 'História']);

        $alvo = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Artigo alvo',
        ]);
        $alvo->categorias()->attach($categoria);
        $alvo->areasTematicas()->attach($area);

        $segundo = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Artigo segunda categoria',
        ]);
        $segundo->categorias()->attach($outraCategoria);
        $segundo->areasTematicas()->attach($area);

        $fora = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Artigo fora',
        ]);
        $fora->categorias()->attach($terceiraCategoria);

        $this->actingAs($usuario)
            ->get(route('artigos.index', [
                'categorias' => [$categoria->id, $outraCategoria->id],
                'areas_tematicas' => [$area->id],
            ]))
            ->assertOk()
            ->assertSee('Artigo alvo')
            ->assertSee('Artigo segunda categoria')
            ->assertDontSee('Artigo fora');
    }

    public function test_usuario_pode_ordenar_artigos_por_titulo(): void
    {
        $usuario = User::factory()->create();
        Artigo::factory()->for($usuario, 'cadastrante')->create(['titulo' => 'Bravo']);
        Artigo::factory()->for($usuario, 'cadastrante')->create(['titulo' => 'Alpha']);

        $html = $this->actingAs($usuario)
            ->get(route('artigos.index', ['ordenar' => 'titulo_asc']))
            ->assertOk()
            ->getContent();

        $this->assertTrue(
            strpos($html, 'Alpha') < strpos($html, 'Bravo'),
            'Artigos deveriam aparecer em ordem alfabética crescente.',
        );
    }

    public function test_usuario_pode_gerenciar_referencias_ordenadas_do_artigo(): void
    {
        $usuario = User::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();

        $this->actingAs($usuario)
            ->put(route('artigos.update', $artigo), [
                'titulo' => $artigo->titulo,
                'slug' => $artigo->slug,
                'resumo' => $artigo->resumo,
                'conteudo' => $artigo->conteudo,
                'status' => StatusArtigo::Rascunho->value,
                'autores' => [$usuario->nome],
                'palavras_chave' => [],
                'referencias' => [
                    'SILVA, João. Memórias do sertão. São Paulo: Editora X, 1998.',
                    'SOUZA, Maria. História oral. Rio de Janeiro: Editora Y, 2005.',
                ],
            ])
            ->assertRedirect(route('artigos.edit', $artigo));

        $this->assertSame(
            [
                'SILVA, João. Memórias do sertão. São Paulo: Editora X, 1998.',
                'SOUZA, Maria. História oral. Rio de Janeiro: Editora Y, 2005.',
            ],
            $artigo->fresh()->referencias->pluck('texto')->all(),
        );

        $this->actingAs($usuario)
            ->put(route('artigos.update', $artigo), [
                'titulo' => $artigo->titulo,
                'slug' => $artigo->slug,
                'resumo' => $artigo->resumo,
                'conteudo' => $artigo->conteudo,
                'status' => StatusArtigo::Rascunho->value,
                'autores' => [$usuario->nome],
                'referencias' => [
                    'SOUZA, Maria. História oral. Rio de Janeiro: Editora Y, 2005.',
                    'SILVA, João. Memórias do sertão. São Paulo: Editora X, 1998.',
                ],
            ])
            ->assertRedirect(route('artigos.edit', $artigo));

        $this->assertSame(
            [
                'SOUZA, Maria. História oral. Rio de Janeiro: Editora Y, 2005.',
                'SILVA, João. Memórias do sertão. São Paulo: Editora X, 1998.',
            ],
            $artigo->fresh()->referencias->pluck('texto')->all(),
        );
    }

    public function test_visitante_pode_abrir_artigo_no_site(): void
    {
        $artigo = Artigo::factory()->publicado()->create([
            'titulo' => 'Memória pública',
            'slug' => 'memoria-publica',
        ]);

        $this->get(route('site.artigos.show', $artigo))
            ->assertOk()
            ->assertSee('Memória pública');
    }

    public function test_edicao_mostra_ver_no_site_mesmo_para_rascunho(): void
    {
        $usuario = User::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Rascunho com prévia',
            'slug' => 'rascunho-com-previa',
            'status' => StatusArtigo::Rascunho,
        ]);

        $this->actingAs($usuario)
            ->get(route('artigos.edit', $artigo))
            ->assertOk()
            ->assertSee('Ver no site')
            ->assertSee('name="acao"', false)
            ->assertSee('value="ver_no_site"', false)
            ->assertSee('form="formulario-artigo"', false)
            ->assertSee("window.open('about:blank', 'memorias_ver_no_site')", false);
    }

    public function test_ver_no_site_salva_antes_de_abrir(): void
    {
        $usuario = User::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Título antigo',
            'slug' => 'titulo-antigo',
        ]);

        $resposta = $this->actingAs($usuario)
            ->put(route('artigos.update', $artigo), [
                'titulo' => 'Título salvo ao ver',
                'slug' => 'titulo-salvo-ao-ver',
                'resumo' => null,
                'conteudo' => 'Corpo salvo',
                'status' => StatusArtigo::Rascunho->value,
                'autores' => [$usuario->nome],
                'palavras_chave' => [],
                'acao' => 'ver_no_site',
            ]);

        $artigoAtualizado = $artigo->fresh();

        $resposta
            ->assertRedirect(route('artigos.edit', $artigoAtualizado))
            ->assertSessionHas('status', 'artigo-atualizado')
            ->assertSessionHas(
                'abrir_site_url',
                route('site.artigos.show', $artigoAtualizado),
            );

        $this->assertDatabaseHas('artigos', [
            'id' => $artigo->id,
            'titulo' => 'Título salvo ao ver',
            'slug' => 'titulo-salvo-ao-ver',
        ]);

        $this->followRedirects($resposta)
            ->assertOk()
            ->assertSee('window.open', false)
            ->assertSee('memorias_ver_no_site', false)
            ->assertSee('abrir-site-fallback', false)
            ->assertSee('titulo-salvo-ao-ver', false);
    }

    public function test_usuario_pode_filtrar_artigos_por_status(): void
    {
        $usuario = User::factory()->create();
        Artigo::factory()->for($usuario, 'cadastrante')->publicado()->create(['titulo' => 'Artigo publicado']);
        Artigo::factory()->for($usuario, 'cadastrante')->create(['titulo' => 'Artigo rascunho']);

        $this->actingAs($usuario)
            ->get(route('artigos.index', ['status' => 'publicados']))
            ->assertOk()
            ->assertSee('Artigo publicado')
            ->assertDontSee('Artigo rascunho');
    }

    public function test_usuario_pode_atualizar_proprio_artigo(): void
    {
        $usuario = User::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Título antigo',
        ]);

        $this->actingAs($usuario)
            ->put(route('artigos.update', $artigo), [
                'titulo' => 'Título novo',
                'slug' => 'titulo-novo',
                'resumo' => null,
                'conteudo' => 'Corpo atualizado',
                'status' => StatusArtigo::Rascunho->value,
                'autores' => [$usuario->nome],
                'palavras_chave' => [],
            ])
            ->assertRedirect(route('artigos.edit', $artigo));

        $this->assertSame('Título novo', $artigo->fresh()->titulo);
        $this->assertSame('titulo-novo', $artigo->fresh()->slug);
    }

    public function test_usuario_pode_atualizar_artigo_de_outro(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        $artigo = Artigo::factory()->for($dono, 'cadastrante')->create();

        $this->actingAs($outro)
            ->put(route('artigos.update', $artigo), [
                'titulo' => 'Editado por outro usuário',
                'slug' => 'editado-por-outro-usuario',
                'conteudo' => 'Conteúdo',
                'status' => StatusArtigo::Rascunho->value,
                'autores' => ['Autor Convidado'],
            ])
            ->assertRedirect(route('artigos.edit', $artigo));

        $this->assertSame('Editado por outro usuário', $artigo->fresh()->titulo);
        $this->assertSame(['Autor Convidado'], $artigo->fresh()->autores->pluck('nome')->all());
    }

    public function test_usuario_pode_excluir_artigo_de_outro(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        $artigo = Artigo::factory()->for($dono, 'cadastrante')->create();

        $this->actingAs($outro)
            ->delete(route('artigos.destroy', $artigo))
            ->assertRedirect(route('artigos.index'));

        $this->assertSoftDeleted($artigo);
    }

    public function test_usuario_pode_excluir_proprio_artigo(): void
    {
        $usuario = User::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();

        $this->actingAs($usuario)
            ->delete(route('artigos.destroy', $artigo))
            ->assertRedirect(route('artigos.index'));

        $this->assertSoftDeleted($artigo);
        $this->assertSame($usuario->id, $artigo->fresh()->deletado_por);
    }

    public function test_usuario_pode_listar_e_restaurar_artigo_excluido(): void
    {
        $usuario = User::factory()->create(['nome' => 'Maria Editora']);
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Artigo na lixeira',
        ]);

        $this->actingAs($usuario)
            ->delete(route('artigos.destroy', $artigo))
            ->assertRedirect(route('artigos.index'));

        $this->actingAs($usuario)
            ->get(route('artigos.index', ['status' => 'excluidos']))
            ->assertOk()
            ->assertSee('Artigo na lixeira')
            ->assertSee('Maria Editora');

        $this->actingAs($usuario)
            ->post(route('artigos.restore', $artigo))
            ->assertRedirect(route('artigos.index', ['status' => 'excluidos']));

        $artigo->refresh();

        $this->assertNotSoftDeleted($artigo);
        $this->assertNull($artigo->deletado_por);
    }

    public function test_usuario_pode_criar_artigo_com_documentos_arquivo_e_url(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $pdf = UploadedFile::fake()->create('anexo.pdf', 120, 'application/pdf');

        $response = $this->actingAs($usuario)->post(route('artigos.store'), [
            'titulo' => 'Artigo com documentos',
            'slug' => 'artigo-com-documentos',
            'conteudo' => 'Conteúdo',
            'status' => StatusArtigo::Rascunho->value,
            'autores' => [$usuario->nome],
            'documentos' => [
                [
                    'titulo' => 'PDF principal',
                    'arquivo' => $pdf,
                ],
                [
                    'titulo' => 'Link externo',
                    'url' => 'https://exemplo.com/memoria.pdf',
                ],
            ],
        ]);

        $artigo = Artigo::query()->firstOrFail();

        $response->assertRedirect(route('artigos.edit', $artigo));
        $this->assertCount(2, $artigo->documentos);
        $this->assertSame(['PDF principal', 'Link externo'], $artigo->documentos->pluck('titulo')->all());
        $this->assertSame([0, 1], $artigo->documentos->pluck('ordem')->all());
        $this->assertNotNull($artigo->documentos[0]->arquivo);
        Storage::disk('public')->assertExists($artigo->documentos[0]->arquivo);
        $this->assertNull($artigo->documentos[0]->url);
        $this->assertNull($artigo->documentos[1]->arquivo);
        $this->assertSame('https://exemplo.com/memoria.pdf', $artigo->documentos[1]->url);
    }

    public function test_usuario_pode_reordenar_e_remover_documentos_do_artigo(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();
        $caminho = 'artigos/documentos/antigo.pdf';
        Storage::disk('public')->put($caminho, 'conteudo-pdf');

        $docArquivo = $artigo->documentos()->create([
            'titulo' => 'Arquivo antigo',
            'arquivo' => $caminho,
            'url' => null,
            'ordem' => 0,
        ]);
        $docUrl = $artigo->documentos()->create([
            'titulo' => 'URL antiga',
            'arquivo' => null,
            'url' => 'https://exemplo.com/antiga.pdf',
            'ordem' => 1,
        ]);

        $this->actingAs($usuario)
            ->put(route('artigos.update', $artigo), [
                'titulo' => $artigo->titulo,
                'slug' => $artigo->slug,
                'conteudo' => $artigo->conteudo,
                'status' => StatusArtigo::Rascunho->value,
                'autores' => [$usuario->nome],
                'documentos' => [
                    [
                        'id' => $docUrl->id,
                        'titulo' => 'URL antiga',
                        'url' => 'https://exemplo.com/antiga.pdf',
                    ],
                ],
            ])
            ->assertRedirect(route('artigos.edit', $artigo));

        $artigo->refresh()->load('documentos');

        $this->assertCount(1, $artigo->documentos);
        $this->assertSame($docUrl->id, $artigo->documentos->first()->id);
        $this->assertSame(0, $artigo->documentos->first()->ordem);
        $this->assertDatabaseMissing('artigo_documentos', ['id' => $docArquivo->id]);
        Storage::disk('public')->assertMissing($caminho);
    }

    public function test_documento_sem_origem_falha_validacao(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post(route('artigos.store'), [
                'titulo' => 'Sem origem',
                'slug' => 'sem-origem',
                'conteudo' => 'Conteúdo',
                'status' => StatusArtigo::Rascunho->value,
                'autores' => [$usuario->nome],
                'documentos' => [
                    [
                        'titulo' => 'Documento incompleto',
                    ],
                ],
            ])
            ->assertSessionHasErrors('documentos.0.arquivo');
    }

    public function test_usuario_pode_enviar_imagem_do_conteudo(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $imagem = UploadedFile::fake()->image('figura.jpg', 800, 600);

        $response = $this->actingAs($usuario)
            ->postJson(route('artigos.conteudo.imagens.store'), [
                'imagem' => $imagem,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['url']);

        $url = $response->json('url');
        $this->assertIsString($url);
        $this->assertStringContainsString('/storage/artigos/conteudo/', $url);

        $arquivos = Storage::disk('public')->allFiles('artigos/conteudo');
        $this->assertCount(1, $arquivos);
        $this->assertStringEndsWith('.webp', $arquivos[0]);
    }

    public function test_envio_de_imagem_do_conteudo_exige_arquivo_valido(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->postJson(route('artigos.conteudo.imagens.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('imagem');
    }
}
