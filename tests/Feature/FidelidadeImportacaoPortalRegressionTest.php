<?php

namespace Tests\Feature;

use App\Enums\StatusArtigo;
use App\Models\Artigo;
use App\Models\User;
use App\Support\Artigos\ExtrairArtigoRicoDoHtmlMemorias;
use App\Support\Artigos\HeuristicaMetadadosPdfMemorias;
use App\Support\Artigos\MontarArtigoLiteralDoPdf;
use App\Support\Artigos\NormalizarCitacoesNoHtml;
use App\Support\Artigos\NormalizarTextoReferencia;
use App\Support\Artigos\PreferirConteudoHtmlOriginal;
use App\Support\Artigos\SanitizarTextoUtf8;
use App\Support\Artigos\VinculaCitacoesNoConteudo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regressões dos erros já vistos no portal × nosso CMS:
 * - autor correspondente só com e-mail
 * - referências com numeração 2x
 * - citações contaminadas com anos/páginas
 */
class FidelidadeImportacaoPortalRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function artigosHtmlAmostra(): array
    {
        $dir = storage_path('app/tmp/portal_samples');
        $ids = ['10817', '11095', '11096', '11098', '11101', '11103', '11108'];

        return array_values(array_filter(array_map(
            static fn (string $id): string => $dir.'/art_'.$id.'.html',
            $ids,
        ), 'is_file'));
    }

    public function test_extracao_html_de_varios_artigos_respeita_regressoes_conhecidas(): void
    {
        $arquivos = $this->artigosHtmlAmostra();
        $this->assertNotEmpty($arquivos, 'Amostras HTML do portal ausentes em storage/app/tmp/portal_samples');

        $extrator = new ExtrairArtigoRicoDoHtmlMemorias;
        $normRef = new NormalizarTextoReferencia;
        $vincular = new VinculaCitacoesNoConteudo;

        foreach ($arquivos as $arquivo) {
            $html = file_get_contents($arquivo);
            $this->assertNotFalse($html, $arquivo);

            $extraido = $extrator->extrair($html);
            $this->assertNotNull($extraido, basename($arquivo));

            $corr = trim(html_entity_decode(strip_tags((string) ($extraido['autor_correspondente'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $corr = trim(preg_replace('/\s+/u', ' ', $corr) ?? $corr);

            if (str_contains($html, 'author-reference') || str_contains($html, 'Financial support')) {
                $this->assertNotSame('', $corr, basename($arquivo).' correspondente vazio');
                $this->assertDoesNotMatchRegularExpression(
                    '/^(\+?\s*)?(Corresponding authors?:\s*)?[^\s<>]+@[^\s<>]+\.[^\s<>]+$/iu',
                    $corr,
                    basename($arquivo).' correspondente só e-mail',
                );
            }

            foreach ($extraido['referencias'] ?? [] as $i => $ref) {
                $this->assertDoesNotMatchRegularExpression(
                    '/^(?:\[\d{1,3}\]|\d{1,3}[.)])\s+/u',
                    (string) $ref,
                    basename($arquivo)." ref #{$i} com numeração embutida",
                );
                $this->assertSame(
                    $normRef->normalizar((string) $ref),
                    $normRef->normalizar($normRef->normalizar((string) $ref)),
                );
            }

            $corpo = (string) ($extraido['conteudo_html'] ?? '');
            if ($corpo === '') {
                continue;
            }

            if (preg_match_all('/<sup>\(([^<]*)\)<\/sup>/u', $corpo, $sups)) {
                foreach ($sups[1] as $interno) {
                    $this->assertDoesNotMatchRegularExpression(
                        '/(?<!\d)(17|18|19|20)\d{2}(?!\d)/',
                        $interno,
                        basename($arquivo).' citação com ano: ('.$interno.')',
                    );
                }
            }

            $vinculado = $vincular($corpo);
            $this->assertDoesNotMatchRegularExpression(
                '/data-refs="[^"]*(?:17|18|19|20)\d{2}/',
                $vinculado,
                basename($arquivo).' data-refs com ano',
            );
        }
    }

    public function test_pipeline_pdf_10817_e_11096_nao_repete_erros_classicos(): void
    {
        $casos = [
            '10817' => [
                'txt' => storage_path('app/tmp/portal_samples/art_10817.txt'),
                'html' => storage_path('app/tmp/portal_samples/art_10817.html'),
                'email' => 'jcosta@ioc.fiocruz.br',
                'citacao' => '/<sup>\(\s*77\s*,\s*78\s*\)<\/sup>/',
            ],
            '11096' => [
                'txt' => storage_path('app/tmp/portal_samples/art_11096.txt'),
                'html' => storage_path('app/tmp/portal_samples/art_11096.html'),
                'email' => 'serviopr@gmail.com',
                'citacao' => null,
            ],
        ];

        foreach ($casos as $id => $caso) {
            if (! is_file($caso['txt']) || ! is_file($caso['html'])) {
                $this->markTestSkipped("Amostra {$id} ausente");
            }

            $texto = (new SanitizarTextoUtf8)->sanitizar((string) file_get_contents($caso['txt']));
            $htmlExtraido = (new ExtrairArtigoRicoDoHtmlMemorias)->extrair((string) file_get_contents($caso['html']));
            $this->assertNotNull($htmlExtraido);

            $meta = (new HeuristicaMetadadosPdfMemorias)->completar($texto, [
                'autor_correspondente' => 'only@email.com',
            ]);

            $montado = (new MontarArtigoLiteralDoPdf)->montar($texto, $meta);
            $mesclado = (new PreferirConteudoHtmlOriginal)->preferir($montado, $htmlExtraido, $texto);

            $corr = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($mesclado['autor_correspondente'] ?? ''))) ?? '');
            $this->assertStringContainsString($caso['email'], $corr, "{$id} email");
            $this->assertDoesNotMatchRegularExpression(
                '/^only@email\.com$/i',
                $corr,
                "{$id} não pode ficar só com e-mail fraco",
            );
            $this->assertTrue(
                str_contains(mb_strtolower($corr), 'financial support')
                || str_contains(mb_strtolower($corr), 'corresponding author')
                || str_contains(mb_strtolower($corr), 'orcid.org'),
                "{$id} correspondente incompleto: {$corr}",
            );

            foreach ($mesclado['referencias'] ?? $montado['referencias'] ?? [] as $ref) {
                $this->assertDoesNotMatchRegularExpression('/^(?:\[\d{1,3}\]|\d{1,3}[.)])\s+/u', (string) $ref);
            }

            $corpo = (string) ($mesclado['conteudo_html'] ?? '');
            if ($caso['citacao'] !== null) {
                $this->assertMatchesRegularExpression($caso['citacao'], $corpo, "{$id} citação");
            }

            $this->assertDoesNotMatchRegularExpression('/<sup>\([^)]*(?:17|18|19|20)\d{2}/', $corpo);
            $this->assertSame(
                $corpo === '' ? '' : (new NormalizarCitacoesNoHtml)->normalizar($corpo),
                $corpo === '' ? '' : (new NormalizarCitacoesNoHtml)->normalizar((new NormalizarCitacoesNoHtml)->normalizar($corpo)),
            );
        }
    }

    public function test_site_e_admin_nao_duplicam_numero_nem_contam_citacao_com_ano(): void
    {
        $usuario = User::factory()->create();
        $artigo = Artigo::factory()->for($usuario, 'cadastrante')->create([
            'titulo' => 'Artigo regressão fidelidade',
            'slug' => 'regressao-fidelidade',
            'conteudo' => '<p>Rhodnius amazonicus in northeastern Brazil.<sup>(77, 1977, 2009, 9, 1, 123, 8, 78, 2017, 176, 364, 72)</sup> However,</p>',
            'autor_correspondente' => '<p>Financial support: CNPq.<br>+ Corresponding author: jcosta@ioc.fiocruz.br<br>https://orcid.org/0000-0001-6664-1394<br>Received 14 January 2021<br>Accepted 04 May 2021</p>',
            'status' => StatusArtigo::Publicado,
            'publicado_em' => now()->subDay(),
        ]);

        $artigo->sincronizarReferencias([
            '71. Dale C, Almeida CE. Title. 2019.',
            '72. Costa J. Another. 2020.',
        ]);

        $this->assertSame(
            [
                'Dale C, Almeida CE. Title. 2019.',
                'Costa J. Another. 2020.',
            ],
            $artigo->fresh()->referencias->pluck('texto')->all(),
        );

        $html = $this->get(route('site.artigos.show', $artigo))
            ->assertOk()
            ->assertSeeText('Dale C, Almeida CE. Title. 2019.')
            ->assertDontSeeText('71. 71')
            ->assertDontSeeText('71. Dale')
            ->assertSee('data-refs="77,78"', false)
            ->assertDontSee('data-refs="77,1977', false)
            ->assertSeeText('Financial support: CNPq')
            ->assertSeeText('jcosta@ioc.fiocruz.br')
            ->getContent();

        $this->assertStringNotContainsString('1977', $html);
        $this->assertStringContainsString('list-decimal', $html);

        $this->actingAs($usuario)
            ->get(route('artigos.edit', $artigo))
            ->assertOk()
            ->assertSee('Autor correspondente', false)
            ->assertSee('jcosta@ioc.fiocruz.br', false)
            ->assertSee('Financial support', false);
    }
}
