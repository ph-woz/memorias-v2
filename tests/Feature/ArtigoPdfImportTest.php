<?php

namespace Tests\Feature;

use App\Actions\Artigos\CriarArtigoAPartirDaExtracaoPdfAction;
use App\Ai\Agents\ExtrairArtigoDoPdf;
use App\Enums\StatusArtigo;
use App\Enums\StatusImportacaoArtigo;
use App\Jobs\ProcessarImportacaoArtigoPdf;
use App\Models\Artigo;
use App\Models\ArtigoImportacao;
use App\Models\User;
use App\Support\Ai\ExtrairArtigoComGemini;
use App\Support\Ai\FormatarConteudoRicoComGemini;
use App\Support\Artigos\EnriquecerHtmlLiteralDoPdf;
use App\Support\Artigos\ExtrairImagensDoPdf;
use App\Support\Artigos\ExtrairTextoPdf;
use App\Support\Artigos\ExtrairTrechosNegritoDoPdf;
use App\Support\Artigos\MontarArtigoLiteralDoPdf;
use App\Support\Artigos\ObterHtmlOriginalDoArtigoPorDoi;
use App\Support\Artigos\PreferirConteudoHtmlOriginal;
use App\Support\Artigos\RefinarMetadadosExtracaoPdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ArtigoPdfImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_pode_enfileirar_importacao_de_pdf(): void
    {
        Storage::fake('public');
        Queue::fake();

        $usuario = User::factory()->create();
        $pdf = UploadedFile::fake()->create('artigo-cientifico.pdf', 500, 'application/pdf');

        $response = $this->actingAs($usuario)->post(route('artigos.importar-pdf'), [
            'pdf' => $pdf,
        ]);

        $importacao = ArtigoImportacao::query()->firstOrFail();

        $response->assertRedirect(route('artigos.importacoes.show', $importacao));
        $this->assertSame(StatusImportacaoArtigo::Pendente, $importacao->status);
        $this->assertSame($usuario->id, $importacao->user_id);
        Storage::disk('public')->assertExists($importacao->pdf_path);
        Queue::assertPushed(ProcessarImportacaoArtigoPdf::class, function (ProcessarImportacaoArtigoPdf $job) use ($importacao): bool {
            return $job->importacao->is($importacao);
        });
    }

    public function test_importacao_sem_pdf_retorna_erro_em_portugues(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->from(route('artigos.create'))
            ->post(route('artigos.importar-pdf'), [])
            ->assertRedirect(route('artigos.create'))
            ->assertSessionHasErrors([
                'pdf' => 'Selecione um arquivo PDF para importar.',
            ]);
    }

    public function test_job_usa_heuristicas_quando_ia_falha(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $caminho = 'artigos/importacoes/teste-heur.pdf';
        Storage::disk('public')->put($caminho, '%PDF-1.4 fake');

        $importacao = ArtigoImportacao::factory()->create([
            'user_id' => $usuario->id,
            'pdf_path' => $caminho,
            'pdf_nome_original' => 'memoria.pdf',
            'status' => StatusImportacaoArtigo::Pendente,
        ]);

        $textoPdf = <<<'TXT'
Mem Inst Oswaldo Cruz, Rio de Janeiro, Vol. 116: e210015, 2021
REVIEW
Do the new triatomine species pose new challenges or strategies for monitoring Chagas disease? An overview from 1979-2021
Jane Costa
1
, Carolina Dale
1
1
Fundação Oswaldo Cruz-Fiocruz, Instituto Oswaldo Cruz, Rio de Janeiro, RJ, Brasil
5
Institut de Recherche pour le Développement, Montpellier, France
Chagas disease persists as one of the most important, and yet most neglected, diseases in the world, and several changes in its epidemiological aspects have been recorded since its discovery.
Key words: American Trypanosomiasis - vectors - Triatominae subfamily
doi: 10.1590/0074-02760210015
+ Corresponding author: jcosta@ioc.fiocruz.br
Accepted 04 May 2021
Despite the great achievements in controlling Chagas disease, also known as American Trypanosomiasis, major problems remain. (2) Vectors such as Triatoma infestans and Rhodnius prolixus continue to challenge control programs.
TXT.str_repeat(' Continuacao fiel do texto do autor.', 25)."\nREFERENCES\n1. Lent H, Wygodzinsky P. Revision. 1979.";

        $this->mock(ExtrairTextoPdf::class, function ($mock) use ($textoPdf): void {
            $mock->shouldReceive('deCaminhoPublico')->once()->andReturn($textoPdf);
        });

        $gemini = Mockery::mock(ExtrairArtigoComGemini::class);
        $gemini->shouldReceive('extrairMetadados')->once()->andThrow(new \RuntimeException('Falha na API Gemini: HTTP 503'));
        $this->app->instance(ExtrairArtigoComGemini::class, $gemini);

        config(['ai.default' => 'gemini']);

        $this->mock(FormatarConteudoRicoComGemini::class, function ($mock): void {
            $mock->shouldReceive('formatarComFallback')->once()->andThrow(new \RuntimeException('Gemini tipografia 503'));
        });

        $this->processarImportacao($importacao);

        $importacao->refresh();
        $artigo = Artigo::query()->firstOrFail();

        $this->assertSame(StatusImportacaoArtigo::Concluida, $importacao->status);
        $this->assertStringContainsString('Do the new triatomine species', $artigo->titulo);
        $this->assertSame('10.1590/0074-02760210015', $artigo->doi);
        $this->assertStringContainsString('Despite the great achievements', $artigo->conteudo);
        $this->assertStringContainsString('Jane Costa', $artigo->autores()->pluck('nome')->implode(', '));
        $this->assertGreaterThan(1, substr_count($artigo->conteudo, '<p'));
        $this->assertStringContainsString('<em>Triatoma infestans</em>', $artigo->conteudo);
        $this->assertStringContainsString('<sup>', $artigo->conteudo);
    }

    public function test_job_cria_rascunho_com_conteudo_literal_do_pdf(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $caminho = 'artigos/importacoes/teste.pdf';
        Storage::disk('public')->put($caminho, '%PDF-1.4 fake');

        $importacao = ArtigoImportacao::factory()->create([
            'user_id' => $usuario->id,
            'pdf_path' => $caminho,
            'pdf_nome_original' => 'memoria.pdf',
            'status' => StatusImportacaoArtigo::Pendente,
        ]);

        $textoPdf = "Memória e saúde pública\nAna Souza\nBruno Lima\nDOI 10.1000/teste.2024\n"
            ."Resumo longo do artigo científico importado.\n"
            .'Introdução original do autor sem qualquer reescrita. Resultados completos do estudo.'
            .str_repeat(' Continuacao fiel do texto. ', 30)
            ."\nREFERENCES\nAutor A. Título. 2020.";

        $this->mock(ExtrairTextoPdf::class, function ($mock) use ($textoPdf): void {
            $mock->shouldReceive('deCaminhoPublico')->once()->andReturn($textoPdf);
        });

        ExtrairArtigoDoPdf::fake([
            [
                'titulo' => 'Memória e saúde pública',
                'slug' => 'memoria-e-saude-publica',
                'doi' => '10.1000/teste.2024',
                'resumo' => 'Resumo longo do artigo científico importado.',
                'autores' => ['Ana Souza', 'Bruno Lima'],
                'endereco_instituicoes' => '',
                'autor_correspondente' => '',
                'palavras_chave' => ['memória', 'saúde'],
                'areas_tematicas' => [],
                'seo_titulo' => 'Memória e saúde pública',
                'seo_descricao' => 'Resumo longo do artigo científico importado.',
                'seo_palavras_chave' => 'memória, saúde',
                'documento_titulo' => 'PDF original',
                'marcador_inicio_corpo' => 'Introdução original do autor sem qualquer reescrita.',
                'marcador_inicio_referencias' => 'REFERENCES',
            ],
        ]);

        $this->processarImportacao($importacao);

        $this->assertSame(100, $importacao->fresh()->progresso);

        $importacao->refresh();
        $artigo = Artigo::query()->firstOrFail();

        $this->assertSame(StatusImportacaoArtigo::Concluida, $importacao->status);
        $this->assertSame($artigo->id, $importacao->artigo_id);
        $this->assertSame(StatusArtigo::Rascunho, $artigo->status);
        $this->assertSame('Memória e saúde pública', $artigo->titulo);
        $this->assertSame('10.1000/teste.2024', $artigo->doi);
        $this->assertSame(['Ana Souza', 'Bruno Lima'], $artigo->autores()->pluck('nome')->all());
        $this->assertStringContainsString('Introdução original do autor sem qualquer reescrita.', $artigo->conteudo);
        $this->assertStringContainsString('Resultados completos do estudo.', $artigo->conteudo);
        $this->assertStringNotContainsString('REFERENCES', $artigo->conteudo);
        $this->assertSame(['Autor A. Título. 2020.'], $artigo->referencias()->pluck('texto')->all());
        $this->assertCount(1, $artigo->documentos);
        ExtrairArtigoDoPdf::assertPromptedTimes(1);
    }

    public function test_status_da_importacao_redireciona_quando_concluida(): void
    {
        $usuario = User::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create();
        $importacao = ArtigoImportacao::factory()->create([
            'user_id' => $usuario->id,
            'artigo_id' => $artigo->id,
            'status' => StatusImportacaoArtigo::Concluida,
        ]);

        $this->actingAs($usuario)
            ->get(route('artigos.importacoes.show', $importacao))
            ->assertRedirect(route('artigos.edit', $artigo));
    }

    public function test_usuario_nao_pode_ver_importacao_de_outro(): void
    {
        $dono = User::factory()->create();
        $outro = User::factory()->create();
        $importacao = ArtigoImportacao::factory()->create([
            'user_id' => $dono->id,
        ]);

        $this->actingAs($outro)
            ->get(route('artigos.importacoes.show', $importacao))
            ->assertForbidden();
    }

    public function test_status_da_importacao_retorna_progresso(): void
    {
        $usuario = User::factory()->create();
        $importacao = ArtigoImportacao::factory()->processando()->create([
            'user_id' => $usuario->id,
            'progresso' => 45,
            'etapa' => 'Extraindo dados com IA...',
        ]);

        $this->actingAs($usuario)
            ->getJson(route('artigos.importacoes.status', $importacao))
            ->assertOk()
            ->assertJson([
                'status' => StatusImportacaoArtigo::Processando->value,
                'progresso' => 45,
                'etapa' => 'Extraindo dados com IA...',
                'finalizada' => false,
            ]);
    }

    public function test_job_nao_duplica_texto_como_print_de_pagina(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $caminho = 'artigos/importacoes/teste-sem-print.pdf';
        Storage::disk('public')->put($caminho, '%PDF-1.4 fake');

        $importacao = ArtigoImportacao::factory()->create([
            'user_id' => $usuario->id,
            'pdf_path' => $caminho,
            'pdf_nome_original' => 'memoria.pdf',
            'status' => StatusImportacaoArtigo::Pendente,
        ]);

        $textoPdf = <<<'TXT'
Mem Inst Oswaldo Cruz, Rio de Janeiro, Vol. 116: e210015, 2021
REVIEW
Do the new triatomine species pose new challenges or strategies for monitoring Chagas disease? An overview from 1979-2021
Jane Costa
1
, Carolina Dale
1
1
Fundação Oswaldo Cruz-Fiocruz, Instituto Oswaldo Cruz, Rio de Janeiro, RJ, Brasil
5
Institut de Recherche pour le Développement, Montpellier, France
Chagas disease persists as one of the most important, and yet most neglected, diseases in the world, and several changes in its epidemiological aspects have been recorded since its discovery.
Key words: American Trypanosomiasis - vectors - Triatominae subfamily
doi: 10.1590/0074-02760210015
+ Corresponding author: jcosta@ioc.fiocruz.br
Accepted 04 May 2021
Despite the great achievements in controlling Chagas disease, also known as American Trypanosomiasis, major problems remain. (2) Vectors such as Triatoma infestans and Rhodnius prolixus continue to challenge control programs.
Valid species and the importance of integrative analysis
TXT.str_repeat(' Continuacao fiel do texto do autor.', 25)."\nREFERENCES\n1. Lent H, Wygodzinsky P. Revision. 1979.";

        $this->mock(ExtrairTextoPdf::class, function ($mock) use ($textoPdf): void {
            $mock->shouldReceive('deCaminhoPublico')->once()->andReturn($textoPdf);
        });

        $gemini = Mockery::mock(ExtrairArtigoComGemini::class);
        $gemini->shouldReceive('extrairMetadados')->once()->andThrow(new \RuntimeException('Falha na API Gemini: HTTP 503'));
        $this->app->instance(ExtrairArtigoComGemini::class, $gemini);

        config(['ai.default' => 'gemini']);

        $this->mock(FormatarConteudoRicoComGemini::class, function ($mock): void {
            $mock->shouldReceive('formatarComFallback')->once()->andReturn([
                'conteudo_html' => '<p>Despite the great achievements in controlling Chagas disease, also known as American Trypanosomiasis, major problems remain. (2) Vectors such as Triatoma infestans and Rhodnius prolixus continue to challenge control programs.</p>'
                    .'<p style="text-align:center"><img src="/storage/artigos/conteudo/x/pagina-2.jpg" alt="pagina-2"></p>'
                    .'<p>Valid species and the importance of integrative analysis</p>',
                'resumo_html' => '<p>Chagas disease persists as one of the most important, and yet most neglected, diseases in the world.</p>',
            ]);
        });

        $this->processarImportacao($importacao, [
            [
                'path' => 'artigos/conteudo/x/pagina-2.jpg',
                'url' => '/storage/artigos/conteudo/x/pagina-2.jpg',
                'alt' => 'pagina-2',
                'bytes' => 80_000,
            ],
            [
                'path' => 'artigos/conteudo/x/pagina-3.jpg',
                'url' => '/storage/artigos/conteudo/x/pagina-3.jpg',
                'alt' => 'pagina-3',
                'bytes' => 70_000,
            ],
            [
                'path' => 'artigos/conteudo/x/fig-1.jpg',
                'url' => '/storage/artigos/conteudo/x/fig-1.jpg',
                'alt' => 'fig-1',
                'bytes' => 12_000,
            ],
        ]);

        $artigo = Artigo::query()->firstOrFail();

        $this->assertSame(StatusImportacaoArtigo::Concluida, $importacao->fresh()->status);
        $this->assertStringContainsString('Despite the great achievements', $artigo->conteudo);
        $this->assertStringContainsString('Valid species and the importance of integrative analysis', $artigo->conteudo);
        $this->assertStringContainsString('<em>Triatoma infestans</em>', $artigo->conteudo);
        $this->assertStringContainsString('fig-1.jpg', $artigo->conteudo);
        $this->assertStringNotContainsString('pagina-2.jpg', $artigo->conteudo);
        $this->assertStringNotContainsString('pagina-3.jpg', $artigo->conteudo);
        $this->assertDoesNotMatchRegularExpression('/<img[^>]+(?<!tabela-)pagina-\d+/i', $artigo->conteudo);
    }

    public function test_job_mantem_raster_de_tabela_landscape_no_conteudo(): void
    {
        Storage::fake('public');

        $usuario = User::factory()->create();
        $caminho = 'artigos/importacoes/teste-tabela-landscape.pdf';
        Storage::disk('public')->put($caminho, '%PDF-1.4 fake');

        $importacao = ArtigoImportacao::factory()->create([
            'user_id' => $usuario->id,
            'pdf_path' => $caminho,
            'pdf_nome_original' => 'memoria.pdf',
            'status' => StatusImportacaoArtigo::Pendente,
        ]);

        $textoPdf = <<<'TXT'
Mem Inst Oswaldo Cruz, Rio de Janeiro, Vol. 116: e210015, 2021
REVIEW
Do the new triatomine species pose new challenges or strategies for monitoring Chagas disease? An overview from 1979-2021
Jane Costa
1
, Carolina Dale
1
1
Fundação Oswaldo Cruz-Fiocruz, Instituto Oswaldo Cruz, Rio de Janeiro, RJ, Brasil
Chagas disease persists as one of the most important, and yet most neglected, diseases in the world, and several changes in its epidemiological aspects have been recorded since its discovery.
Key words: American Trypanosomiasis - vectors - Triatominae subfamily
doi: 10.1590/0074-02760210015
+ Corresponding author: jcosta@ioc.fiocruz.br
Accepted 04 May 2021
Despite the great achievements in controlling Chagas disease. (2) Vectors such as Triatoma infestans continue to challenge control programs.
Table I summarizes Type locality Distribution Collection Ecotope DNA sequence for Herman Lent Collection.
TXT.str_repeat(' Continuacao fiel do texto do autor.', 20)."\nREFERENCES\n1. Lent H, Wygodzinsky P. Revision. 1979.";

        $this->mock(ExtrairTextoPdf::class, function ($mock) use ($textoPdf): void {
            $mock->shouldReceive('deCaminhoPublico')->once()->andReturn($textoPdf);
        });

        $gemini = Mockery::mock(ExtrairArtigoComGemini::class);
        $gemini->shouldReceive('extrairMetadados')->once()->andThrow(new \RuntimeException('Falha na API Gemini: HTTP 503'));
        $this->app->instance(ExtrairArtigoComGemini::class, $gemini);

        config(['ai.default' => 'gemini']);

        $this->mock(FormatarConteudoRicoComGemini::class, function ($mock): void {
            $mock->shouldReceive('formatarComFallback')->once()->andReturn([
                'conteudo_html' => '<p>Despite the great achievements in controlling Chagas disease. (2) Vectors such as Triatoma infestans continue to challenge control programs.</p>'
                    .'<p>Table I summarizes Type locality Distribution Collection Ecotope DNA sequence for Herman Lent Collection.</p>',
                'resumo_html' => '<p>Chagas disease persists as one of the most important, and yet most neglected, diseases in the world.</p>',
            ]);
        });

        $this->processarImportacao($importacao, [
            [
                'path' => 'artigos/conteudo/x/tabela-pagina-4.jpg',
                'url' => '/storage/artigos/conteudo/x/tabela-pagina-4.jpg',
                'alt' => 'tabela-pagina-4',
                'bytes' => 90_000,
            ],
            [
                'path' => 'artigos/conteudo/x/fig-1.jpg',
                'url' => '/storage/artigos/conteudo/x/fig-1.jpg',
                'alt' => 'fig-1',
                'bytes' => 12_000,
            ],
        ]);

        $artigo = Artigo::query()->firstOrFail();

        $this->assertSame(StatusImportacaoArtigo::Concluida, $importacao->fresh()->status);
        $this->assertStringContainsString('tabela-pagina-4.jpg', $artigo->conteudo);
        $this->assertStringContainsString('fig-1.jpg', $artigo->conteudo);
        $this->assertMatchesRegularExpression('/tabela-pagina-\d+|<\s*table\b/i', $artigo->conteudo);
    }

    /**
     * @param  list<array{path: string, url: string, alt?: string, bytes?: int}>|null  $imagensExtraidas
     */
    private function processarImportacao(ArtigoImportacao $importacao, ?array $imagensExtraidas = null): void
    {
        Http::fake([
            'api.crossref.org/*' => Http::response(['status' => 'ok', 'message' => []], 200),
            'www.scielo.br/*' => Http::response('not found', 404),
            'scielo.br/*' => Http::response('not found', 404),
        ]);

        $imagens = Mockery::mock(ExtrairImagensDoPdf::class);
        $imagens->shouldReceive('deCaminhoPublico')->andReturn($imagensExtraidas ?? [
            [
                'path' => 'artigos/conteudo/fig-1.jpg',
                'url' => '/storage/artigos/conteudo/fig-1.jpg',
                'alt' => 'fig-1',
                'bytes' => 12000,
            ],
        ]);

        $negrito = Mockery::mock(ExtrairTrechosNegritoDoPdf::class);
        $negrito->shouldReceive('deCaminhoPublico')->andReturn([]);

        (new ProcessarImportacaoArtigoPdf($importacao))->handle(
            app(CriarArtigoAPartirDaExtracaoPdfAction::class),
            app(ExtrairTextoPdf::class),
            app(ExtrairArtigoComGemini::class),
            app(RefinarMetadadosExtracaoPdf::class),
            app(MontarArtigoLiteralDoPdf::class),
            $imagens,
            $negrito,
            app(FormatarConteudoRicoComGemini::class),
            app(EnriquecerHtmlLiteralDoPdf::class),
            app(ObterHtmlOriginalDoArtigoPorDoi::class),
            app(PreferirConteudoHtmlOriginal::class),
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
