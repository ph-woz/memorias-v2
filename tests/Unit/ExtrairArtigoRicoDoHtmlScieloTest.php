<?php

namespace Tests\Unit;

use App\Support\Artigos\ExtrairArtigoRicoDoHtmlScielo;
use PHPUnit\Framework\TestCase;

class ExtrairArtigoRicoDoHtmlScieloTest extends TestCase
{
    public function test_extrai_corpo_tipografia_e_referencias_do_html_scielo(): void
    {
        $html = file_get_contents(__DIR__.'/../Fixtures/Artigos/scielo_artigo_minimo.html');
        $this->assertNotFalse($html);

        $resultado = (new ExtrairArtigoRicoDoHtmlScielo)->extrair($html);

        $this->assertNotNull($resultado);
        $this->assertSame('Do the new triatomine species pose new challenges?', $resultado['titulo']);
        $this->assertSame('10.1590/0074-02760210015', $resultado['doi']);
        $this->assertStringStartsWith('Despite the great achievements', strip_tags($resultado['conteudo_html']));
        $this->assertStringContainsString('<em>Triatoma infestans</em>', $resultado['conteudo_html']);
        $this->assertStringContainsString('<em>T. infestans</em>', $resultado['conteudo_html']);
        $this->assertStringContainsString('<sup>(2)</sup>', $resultado['conteudo_html']);
        $this->assertStringContainsString('<h2><strong>ACKNOWLEDGEMENTS</strong></h2>', $resultado['conteudo_html']);
        $this->assertSame(1, substr_count(strtoupper($resultado['conteudo_html']), 'ACKNOWLEDGEMENTS'));
        $this->assertStringNotContainsString('h5', $resultado['conteudo_html']);
        $this->assertStringContainsString('<h2><strong>AUTHORS’ CONTRIBUTION</strong></h2>', $resultado['conteudo_html']);
        $this->assertStringNotContainsString('Author contributions', $resultado['conteudo_html']);
        $this->assertStringContainsString('JC - Conceptualisation', $resultado['conteudo_html']);
        $this->assertStringContainsString('<p>', (string) $resultado['resumo']);
        $this->assertStringContainsString('Chagas disease persists', strip_tags((string) $resultado['resumo']));
        $this->assertContains('American Trypanosomiasis', $resultado['palavras_chave']);
        $this->assertCount(3, $resultado['referencias']);
        $this->assertDoesNotMatchRegularExpression('/^\d/', $resultado['referencias'][0]);
        $this->assertNotSame('', $resultado['referencias'][0]);
    }

    public function test_remove_titulo_colado_no_paragrafo_da_secao(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html><body>
<h1 class="article-title">Sample title for section strip</h1>
<meta name="citation_doi" content="10.1590/0074-02760210015" />
<div class="articleSection" data-anchor="Text"><p>Body paragraph of the article.</p></div>
<div class="articleSection" data-anchor="ACKNOWLEDGEMENTS">
  <p>ACKNOWLEDGEMENTS To colleagues who contributed to this review.</p>
</div>
</body></html>
HTML;

        $resultado = (new ExtrairArtigoRicoDoHtmlScielo)->extrair($html);

        $this->assertNotNull($resultado);
        $this->assertSame(1, substr_count(strtoupper($resultado['conteudo_html']), 'ACKNOWLEDGEMENTS'));
        $this->assertStringContainsString('To colleagues who contributed', $resultado['conteudo_html']);
    }
}
