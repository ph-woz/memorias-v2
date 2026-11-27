<?php

namespace Tests\Unit;

use App\Support\Artigos\EnriquecerHtmlLiteralDoPdf;
use PHPUnit\Framework\TestCase;

class EnriquecerHtmlLiteralDoPdfTest extends TestCase
{
    public function test_preserva_paragrafo_unico_do_pdf_e_marca_especies_citacoes_e_imagens(): void
    {
        $html = '<p>Despite the great achievements in controlling Chagas disease. '
            .'No vaccines are available for Triatoma infestans vectors. (2) '
            .'More text continues with Rhodnius prolixus in the region. (3, 4) '
            .str_repeat('Continuacao fiel do texto do autor. ', 40)
            .'</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->enriquecer($html, [
            ['path' => 'x.jpg', 'url' => '/storage/artigos/conteudo/fig-1.jpg', 'alt' => 'fig01'],
        ]);

        // PDF manda: um <p> longo permanece um <p> — sem inventar quebras por tamanho.
        // (imagens legítimas usam <p style="text-align:center"> e não contam aqui)
        $this->assertSame(1, substr_count($resultado, '<p>'));
        $this->assertStringNotContainsString('</p><p>Continuacao', $resultado);
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

    public function test_preserva_table_html_e_remove_raster_de_tabela_landscape(): void
    {
        $html = '<p>Material and methods describe sampling.</p>'
            .'<table><caption>TABLE I</caption><thead><tr><th>Type locality</th><th>Distribution</th><th>Collection</th><th>Ecotope</th><th>DNA sequence</th></tr></thead>'
            .'<tbody><tr><td>Jacundá</td><td>Pará, Brazil</td><td>USP</td><td>Dwellings</td><td>18S</td></tr></tbody></table>'
            .'<p>Table I summarizes type locality Distribution Collection Ecotope DNA sequence data.</p>'
            .'<p style="text-align:center"><img src="/storage/artigos/conteudo/x/pagina-4.jpg" alt="pagina-4"></p>'
            .'<p style="text-align:center"><img src="/storage/artigos/conteudo/x/tabela-pagina-4.jpg" alt="tabela-pagina-4"></p>'
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
                'path' => 'artigos/conteudo/x/fig-1.jpg',
                'url' => '/storage/artigos/conteudo/x/fig-1.jpg',
                'alt' => 'fig-1',
            ],
        ]);

        $this->assertStringContainsString('<table', $resultado);
        $this->assertMatchesRegularExpression('/TABLE\s*I/i', $resultado);
        $this->assertStringContainsString('fig-1.jpg', $resultado);
        $this->assertStringNotContainsString('pagina-4.jpg', $resultado);
        $this->assertStringNotContainsString('tabela-pagina-4.jpg', $resultado);
        $this->assertStringNotContainsString('type locality Distribution Collection Ecotope DNA sequence', $resultado);
        $posTabela = stripos($resultado, '<table');
        $posReferences = stripos($resultado, 'REFERENCES');
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

    public function test_nao_aplica_negrito_de_subtitulo_no_meio_da_prosa(): void
    {
        $html = '<p>regarded as target species.</p>'
            .'<p>These<strong>native vectors</strong>are occasionally found in households, but usually without robust signs of colonisation.'
            .'<sup>(47)</sup><em>Rhodnius prolixus</em>is the main Chagas disease vector in Venezuela.</p>'
            .'<p>Native vectors</p>'
            .'<p>More than ten different species of triatomines have been found.</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html, [], [
            'Target species',
            'Native vectors',
        ]);

        $this->assertStringNotContainsString('<strong>native vectors</strong>', $resultado);
        $this->assertStringNotContainsString('<strong>target species</strong>', $resultado);
        $this->assertStringContainsString('These native vectors are occasionally', $resultado);
        $this->assertMatchesRegularExpression(
            '/colonisation\.\s*<sup>\(47\)<\/sup>\s*<em>Rhodnius prolixus<\/em>\s*is the main/u',
            $resultado,
        );
        $this->assertMatchesRegularExpression(
            '/<(?:p|h2)><strong>Native vectors<\/strong><\/(?:p|h2)>/',
            $resultado,
        );
    }

    public function test_separa_subtitulo_negrito_colado_no_fim_do_paragrafo(): void
    {
        $html = '<p>they are not currently regarded as target species. Native vectors</p>'
            .'<p>More than ten different species of triatomines have been found.</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html, [], ['Native vectors']);

        $this->assertStringContainsString('regarded as target species.</p>', $resultado);
        $this->assertMatchesRegularExpression(
            '/<(?:p|h2)><strong>Native vectors<\/strong><\/(?:p|h2)>/',
            $resultado,
        );
        $this->assertStringNotContainsString('target species. Native vectors</p>', $resultado);
    }

    public function test_separa_subtitulo_negrito_colado_no_meio_do_paragrafo(): void
    {
        $html = '<p>In this review we contextualise the new vector species regarding their ecological '
            .'characteristics, epidemiologic importance, and the new obstacles they pose to the monitoring '
            .'and control of Chagas disease around the world.'
            .'<strong>Triatominae until 1979</strong>'
            .'Triatomines have been known since the 18th century, when the first species,'
            .'<em>T. rubrofasciata</em>(De Geer, 1773), was described in Indonesia.</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html, [], ['Triatominae until 1979']);

        $this->assertMatchesRegularExpression(
            '/around the world\.\s*<\/p>\s*<(?:p|h2)><strong>Triatominae until 1979<\/strong><\/(?:p|h2)>\s*<p>/i',
            $resultado,
        );
        $this->assertStringContainsString('Triatomines have been known since the 18th century', $resultado);
        $this->assertMatchesRegularExpression('/<em>T\. rubrofasciata<\/em>\s*\(De Geer, 1773\)/u', $resultado);
        $this->assertStringNotContainsString('world.<strong>Triatominae', $resultado);
        $this->assertStringNotContainsString('1979</strong>Triatomines', $resultado);
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

    public function test_promove_acknowledgements_colado_no_fim_do_paragrafo_para_h2(): void
    {
        $html = '<p>may contribute to keeping endemic areas free of Chagas disease.'
            .'<sup>108</sup><strong> ACKNOWLEDGEMENTS</strong></p>'
            .'<p>To Dr Andrew Townsend Peterson, Biodiversity Center, Kansas University.</p>'
            .'<h2><strong>AUTHORS’ CONTRIBUTION</strong></h2>'
            .'<p>JC - Conceptualisation and writing.</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html, [], ['ACKNOWLEDGEMENTS']);

        $this->assertStringContainsString('<h2><strong>ACKNOWLEDGEMENTS</strong></h2>', $resultado);
        $this->assertStringContainsString('<h2><strong>AUTHORS’ CONTRIBUTION</strong></h2>', $resultado);
        $this->assertSame(1, substr_count(strtoupper($resultado), 'ACKNOWLEDGEMENTS'));
        $this->assertDoesNotMatchRegularExpression(
            '/disease\.[^<]*<sup>108<\/sup>\s*<strong>\s*ACKNOWLEDGEMENTS\s*<\/strong>\s*<\/p>/i',
            $resultado,
        );
        $this->assertStringContainsString('free of Chagas disease.', $resultado);
        $this->assertStringContainsString('To Dr Andrew Townsend Peterson', $resultado);
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

    public function test_nao_abre_paragrafo_novo_apos_chamada_table(): void
    {
        $html = '<p>'.str_repeat('Triatomine species described after 1979 were recorded in several countries. ', 8)
            .'from 15 countries,<sup>(52, 53)</sup> plus a subspecies<sup>(69)</sup>(Table).</p>'
            .'<p>Out of those 47 triatomines, 17 are from Brazil, followed by Mexico and Colombia.</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html);

        $this->assertStringContainsString('(Table). Out of those 47 triatomines', $resultado);
        $this->assertStringNotContainsString('(Table).</p><p>Out of those', $resultado);
    }

    public function test_funde_falso_paragrafo_antes_de_autor_taxonomico(): void
    {
        $html = '<p>targeting Triatoma infestans (Klug, 1834), Rhodnius prolixus Stål, 1859, and T. dimidiata</p>'
            .'<p>(Latreille, 1811) (northern South America and Central America).</p>'
            .'<p>The most remarkable achievement resulting from these initiatives was the elimination.</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html);

        $this->assertStringContainsString('T. dimidiata</em> (Latreille, 1811)', $resultado);
        $this->assertDoesNotMatchRegularExpression(
            '/dimidiata<\/em>\s*<\/p>\s*<p[^>]*>\s*\(Latreille/i',
            $resultado,
        );
        $this->assertStringContainsString('Central America).</p><p>The most remarkable', $resultado);
    }

    public function test_insere_espacos_ao_redor_de_em_e_sup_colados(): void
    {
        $html = '<p>agent Trypanosoma cruzi:<em>Triatoma infestans</em>(Klug, 1834) (southern South America),'
            .'<em>Rhodnius prolixus</em>Stål, 1859, and<em>T. dimidiata</em> (Latreille, 1811).</p>'
            .'<p>elimination of the<em>T. infestans</em>domiciliated populations in Brazil.<sup>8</sup>'
            .'More recently, the elimination of<em>R. prolixus</em>from Central America.<sup>9,10</sup>has'
            .' also been considered.</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html);

        $this->assertStringContainsString('cruzi: <em>', $resultado);
        $this->assertStringContainsString('</em> (Klug, 1834)', $resultado);
        $this->assertStringContainsString('</em> Stål, 1859', $resultado);
        $this->assertStringContainsString('and <em>T. dimidiata</em>', $resultado);
        $this->assertStringContainsString('the <em>T. infestans</em> domiciliated', $resultado);
        $this->assertStringContainsString('of <em>R. prolixus</em> from', $resultado);
        $this->assertStringContainsString('</sup> More recently', $resultado);
        $this->assertStringContainsString('</sup> has also', $resultado);
        $this->assertStringNotContainsString('infestans</em>(Klug', $resultado);
        $this->assertStringNotContainsString('prolixus</em>Stål', $resultado);
        $this->assertStringNotContainsString('infestans</em>domiciliated', $resultado);
        $this->assertStringNotContainsString('prolixus</em>from', $resultado);
    }

    public function test_nao_parte_paragrafo_apos_abreviacao_de_genero(): void
    {
        $fraseBase = 'Four multigovernmental initiatives were launched targeting the three most important '
            .'triatomine species that transmit the etiological agent Trypanosoma cruzi: ';
        $html = '<p>'.str_repeat($fraseBase, 3)
            .'Triatoma infestans (Klug, 1834) (southern South America), Rhodnius prolixus Stål, 1859, '
            .'and T. dimidiata (Latreille, 1811) (northern South America and Central America).</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->polir($html);

        $this->assertSame(1, substr_count(strtolower($resultado), '<p'));
        $this->assertDoesNotMatchRegularExpression(
            '/\bT\.\s*<\/p>\s*<p/i',
            $resultado,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/dimidiata<\/em>\s*<\/p>\s*<p[^>]*>\s*\(Latreille/i',
            $resultado,
        );
        $this->assertStringContainsString('T. dimidiata</em> (Latreille, 1811)', $resultado);
    }

    public function test_reune_frase_cortada_e_reposiciona_tabela_apos_table_figure(): void
    {
        $tabela = '<div class="tabela-pdf-scroll"><table><caption>TABLE I</caption>'
            .'<thead><tr><th>Genus</th><th>Species</th></tr></thead>'
            .'<tbody><tr><td>Alberprosenia</td><td>malheiroi</td></tr></tbody></table></div>';

        $html = '<p>Twenty-one taxa belong to Triatoma and the numbers varied from one to two in each one (Table, Figure).</p>'
            .'<p>Twenty-one of the 47 new taxa belong to the genus Triatoma.</p>'
            .'<p>Variety of ecotopes and the challenges of the control programs</p>'
            .'<p>In addition to the domiciliary infestation, it is important to mention the vector control '
            .'programs are not strongly supported by governmental institutions or are</p>'
            .$tabela
            .'<p>Numbers represent the type localities listed in the table. almost inexistent in some of the '
            .'endemic countries.<sup>(98)</sup></p>'
            .'<p>Furthermore, there are difficulties in implementing continuous vector control.</p>';

        $resultado = (new EnriquecerHtmlLiteralDoPdf)->garantirTabelaHtml($html, []);

        $this->assertStringContainsString(
            'governmental institutions or are almost inexistent in some of the endemic countries',
            $resultado,
        );
        $this->assertStringNotContainsString('institutions or are</p>', $resultado);
        $this->assertStringNotContainsString('Numbers represent the type localities listed in the table. almost', $resultado);

        $posChamada = stripos($resultado, '(Table, Figure)');
        $posTabela = stripos($resultado, '<table');
        $posInaddition = stripos($resultado, 'In addition to the domiciliary');
        $this->assertNotFalse($posChamada);
        $this->assertNotFalse($posTabela);
        $this->assertNotFalse($posInaddition);
        $this->assertLessThan($posTabela, $posChamada); // PHPUnit: actual < expected → chamada < tabela
        $this->assertLessThan($posInaddition, $posTabela);
    }
}
