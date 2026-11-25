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
        $this->assertStringContainsString('<p>', (string) $resultado['resumo']);
        $this->assertStringContainsString('Chagas disease persists', strip_tags((string) $resultado['resumo']));
        $this->assertContains('American Trypanosomiasis', $resultado['palavras_chave']);
        $this->assertCount(3, $resultado['referencias']);
        $this->assertStringStartsWith('1 ', $resultado['referencias'][0]);
    }
}
