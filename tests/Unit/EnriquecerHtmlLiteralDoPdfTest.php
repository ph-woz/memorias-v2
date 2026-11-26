<?php

namespace Tests\Unit;

use App\Support\Artigos\EnriquecerHtmlLiteralDoPdf;
use PHPUnit\Framework\TestCase;

class EnriquecerHtmlLiteralDoPdfTest extends TestCase
{
    public function test_reparte_paragrafos_marca_especies_citacoes_e_insere_imagens(): void
    {
        $html = '<p>Despite the great achievements in controlling Chagas disease. '
            .'No vaccines are available for Triatoma infestans vectors. (2) '
            .'More text continues with Rhodnius prolixus in the region. (3, 4) '
            .str_repeat('Continuacao fiel do texto do autor. ', 40)
            .'</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->enriquecer($html, [
            ['path' => 'x.jpg', 'url' => '/storage/artigos/conteudo/fig-1.jpg', 'alt' => 'fig01'],
        ]);

        $this->assertGreaterThan(1, substr_count($resultado, '<p'));
        $this->assertStringContainsString('<em>Triatoma infestans</em>', $resultado);
        $this->assertStringContainsString('<em>Rhodnius prolixus</em>', $resultado);
        $this->assertStringContainsString('<sup>(2)</sup>', $resultado);

        $comAbreviacao = (new EnriquecerHtmlLiteralDoPdf)->polir(
            '<p>Transmission by T. infestans and R. prolixus remains relevant. (5)</p>'
        );
        $this->assertStringContainsString('<em>T. infestans</em>', $comAbreviacao);
        $this->assertStringContainsString('<em>R. prolixus</em>', $comAbreviacao);
        $this->assertStringNotContainsString('<em>Resultados completos</em>', $resultado);
        $this->assertStringContainsString('<img src="/storage/artigos/conteudo/fig-1.jpg"', $resultado);
    }

    public function test_polir_completa_tipografia_pobre_da_ia_sem_destruir_tags(): void
    {
        $html = '<p>Despite the great achievements. Triatoma infestans remains. (2)</p>'
            .'<p>Introduction</p>'
            .'<p>Already marked <em>Panstrongylus megistus</em> stays. (3)</p>'
            .'<p>New species challenges</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html, [
            ['path' => 'a.jpg', 'url' => '/storage/artigos/conteudo/a.jpg', 'alt' => 'a'],
            ['path' => 'b.jpg', 'url' => '/storage/artigos/conteudo/b.jpg', 'alt' => 'b'],
        ]);

        $this->assertStringContainsString('<em>Triatoma infestans</em>', $resultado);
        $this->assertStringContainsString('<em>Panstrongylus megistus</em>', $resultado);
        $this->assertSame(1, substr_count($resultado, '<em>Panstrongylus megistus</em>'));
        $this->assertStringNotContainsString('<em><em>', $resultado);
        $this->assertStringContainsString('<sup>(2)</sup>', $resultado);
        $this->assertStringContainsString('<h2><strong>Introduction</strong></h2>', $resultado);
        $this->assertMatchesRegularExpression('/<(strong|h2)>New species challenges<\/(strong|h2)>/', $resultado);
        $this->assertStringContainsString('<img src="/storage/artigos/conteudo/a.jpg"', $resultado);
        $this->assertStringContainsString('<img src="/storage/artigos/conteudo/b.jpg"', $resultado);
    }

    public function test_desifeniza_silabas_partidas_do_pdf(): void
    {
        $html = '<p>Despite controlling Cha- gas disease and epidemiol- ogy changes.</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html);

        $this->assertStringContainsString('Chagas', $resultado);
        $this->assertStringContainsString('epidemiology', $resultado);
        $this->assertStringNotContainsString('Cha- gas', $resultado);
        $this->assertStringNotContainsString('epidemiol- ogy', $resultado);
    }

    public function test_nao_insere_nem_mantem_print_de_pagina_de_texto(): void
    {
        $html = '<p>Valid species and the importance of integrative analysis.</p>'
            .'<p style="text-align:center"><img src="/storage/artigos/conteudo/x/pagina-2.jpg" alt="pagina-2"></p>'
            .'<p>More literal body text about Triatoma infestans. (2)</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html, [
            [
                'path' => 'artigos/conteudo/x/pagina-3.jpg',
                'url' => '/storage/artigos/conteudo/x/pagina-3.jpg',
                'alt' => 'pagina-3',
            ],
            [
                'path' => 'artigos/conteudo/x/fig-1.jpg',
                'url' => '/storage/artigos/conteudo/x/fig-1.jpg',
                'alt' => 'fig-1',
            ],
        ]);

        $this->assertStringNotContainsString('pagina-2.jpg', $resultado);
        $this->assertStringNotContainsString('pagina-3.jpg', $resultado);
        $this->assertStringContainsString('fig-1.jpg', $resultado);
        $this->assertStringContainsString('<em>Triatoma infestans</em>', $resultado);
    }

    public function test_preserva_e_insere_raster_de_tabela_landscape(): void
    {
        $html = '<p>Material and methods describe sampling.</p>'
            .'<p>Table I summarizes type locality and DNA sequence data.</p>'
            .'<p style="text-align:center"><img src="/storage/artigos/conteudo/x/pagina-4.jpg" alt="pagina-4"></p>'
            .'<h2><strong>REFERENCES</strong></h2>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html, [
            [
                'path' => 'artigos/conteudo/x/pagina-4.jpg',
                'url' => '/storage/artigos/conteudo/x/pagina-4.jpg',
                'alt' => 'pagina-4',
            ],
            [
                'path' => 'artigos/conteudo/x/tabela-pagina-4.jpg',
                'url' => '/storage/artigos/conteudo/x/tabela-pagina-4.jpg',
                'alt' => 'tabela-pagina-4',
            ],
            [
                'path' => 'artigos/conteudo/x/tabela-pagina-5.jpg',
                'url' => '/storage/artigos/conteudo/x/tabela-pagina-5.jpg',
                'alt' => 'tabela-pagina-5',
            ],
        ]);

        $this->assertDoesNotMatchRegularExpression('/(?<!tabela-)pagina-4\.jpg/', $resultado);
        $this->assertStringContainsString('tabela-pagina-4.jpg', $resultado);
        $this->assertStringContainsString('tabela-pagina-5.jpg', $resultado);
        $posTabela = strpos($resultado, 'tabela-pagina-4.jpg');
        $posReferences = strpos($resultado, 'REFERENCES');
        $this->assertNotFalse($posTabela);
        $this->assertNotFalse($posReferences);
        $this->assertLessThan($posReferences, $posTabela);
    }

    public function test_aplica_negrito_detectado_nas_fontes_do_pdf(): void
    {
        $html = '<p>Despite the great achievements in controlling Chagas disease around the world.</p>'
            .'<p>Triatominae until 1979</p>'
            .'<p>Triatomines have been known since the 18th century, when the first species, <em>T. rubrofasciata</em>, was described.</p>'
            .'<p>Valid species and the importance of integrative analysis</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html, [], [
            'Triatominae until 1979',
            'Valid species and the importance of integrative analysis',
        ]);

        $this->assertStringContainsString('<strong>Triatominae until 1979</strong>', $resultado);
        $this->assertStringContainsString('<strong>Valid species and the importance of integrative analysis</strong>', $resultado);
        $this->assertStringNotContainsString('<p>Triatominae until 1979</p>', $resultado);
        $this->assertStringContainsString('<em>T. rubrofasciata</em>', $resultado);
    }

    public function test_remove_titulos_de_secao_duplicados_consecutivos(): void
    {
        $html = '<p>Final sentence of the discussion.(108)</p>'
            .'<h2><strong>ACKNOWLEDGEMENTS</strong></h2>'
            .'<h2 class="h5">ACKNOWLEDGEMENTS</h2>'
            .'<p>To Dr Andrew Townsend Peterson.</p>'
            .'<h2><strong>AUTHORS’ CONTRIBUTION</strong></h2>'
            .'<h2 class="h5">Author contributions</h2>'
            .'<p>JC - Conceptualisation and writing.</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html);

        $this->assertSame(1, substr_count(strtoupper($resultado), 'ACKNOWLEDGEMENTS'));
        $this->assertStringContainsString('To Dr Andrew Townsend Peterson.', $resultado);
        // Títulos com texto diferente não são colapsados pelo dedupe idêntico.
        $this->assertStringContainsString('AUTHORS’ CONTRIBUTION', $resultado);
        $this->assertStringContainsString('Author contributions', $resultado);
    }

    public function test_polir_campo_rico_converte_texto_e_preserva_html(): void
    {
        $enriquecedor = new EnriquecerHtmlLiteralDoPdf;

        $deTexto = $enriquecedor->polirCampoRico(
            "BACKGROUND Extracellular vesicles.\n\nCorresponding author: jcosta@ioc.fiocruz.br"
        );
        $this->assertStringContainsString('<p>', $deTexto);
        $this->assertStringContainsString('BACKGROUND Extracellular vesicles.', $deTexto);
        $this->assertStringNotContainsString('<img', $deTexto);

        $deHtml = $enriquecedor->polirCampoRico(
            '<p>Already marked <em>Triatoma infestans</em> stays.</p>'
        );
        $this->assertStringContainsString('<em>Triatoma infestans</em>', $deHtml);
        $this->assertSame(1, substr_count($deHtml, '<em>Triatoma infestans</em>'));
    }
}
