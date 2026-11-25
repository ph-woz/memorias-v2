<?php

namespace Tests\Unit;

use App\Support\Artigos\ExtrairArtigoRicoDoHtmlMemorias;
use PHPUnit\Framework\TestCase;

class ExtrairArtigoRicoDoHtmlMemoriasTest extends TestCase
{
    public function test_nao_usa_bloco_de_referencias_como_corpo_e_expoe_pdf(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html><body>
<div class="title"><h1>Titulo de artigo moderno</h1></div>
<div class="authors"><p>Ana Souza<sup>1</sup>, Bruno Lima<sup>2</sup></p></div>
<div class="institutions"><p><sup>1</sup>Fiocruz, RJ, Brasil</p></div>
<span class="doi">doi: 10.1590/0074-02760250109</span>
<article class="full-article">
  <div class="full-content">
    <b>ABSTRACT</b>
    <div id="textabstract">
      <p><strong>BACKGROUND</strong> Texto do abstract.
      <br><strong>OBJECTIVES</strong> Avaliar algo.
      <br><strong>METHODS</strong> Metodo.
      <br><strong>FINDINGS</strong> Achados.
      <br><strong>MAIN CONCLUSIONS</strong> Conclusao.</p>
    </div>
  </div>
</article>
<article class="full-article">
  <div class="full-content">
    <div class="references">
      <b>REFERENCES</b>
      <div class="reference" id="big-reference1"><span class="number">01.</span> Autor A. Titulo. 2020.</div>
      <div class="reference" id="big-reference2"><span class="number">02.</span> Autor B. Outro. 2021.</div>
      <div class="reference" id="big-reference3"><span class="number">03.</span> Autor C. Mais. 2022.</div>
    </div>
  </div>
</article>
<aside id="references">
  <div class="references">
    <div class="reference"><span class="number">01.</span> Autor A. Titulo. 2020.<div class="links-reference">x</div></div>
    <div class="reference"><span class="number">02.</span> Autor B. Outro. 2021.</div>
    <div class="reference"><span class="number">03.</span> Autor C. Mais. 2022.</div>
  </div>
</aside>
<a href="/article/14397?task=artigo.download">PDF</a>
</body></html>
HTML;

        $resultado = (new ExtrairArtigoRicoDoHtmlMemorias)->extrair($html);

        $this->assertNotNull($resultado);
        $this->assertSame('Titulo de artigo moderno', $resultado['titulo']);
        $this->assertSame('10.1590/0074-02760250109', $resultado['doi']);
        $this->assertStringContainsString('BACKGROUND', (string) $resultado['resumo']);
        $this->assertSame('', $resultado['conteudo_html']);
        $this->assertTrue($resultado['corpo_somente_pdf']);
        $this->assertStringContainsString('task=artigo.download', (string) $resultado['pdf_url']);
        $this->assertCount(3, $resultado['referencias']);
        $this->assertStringStartsWith('01.', $resultado['referencias'][0]);
        $this->assertStringNotContainsString('REFERENCES', $resultado['referencias'][0]);
    }

    public function test_extrai_corpo_rico_quando_ha_paragrafos_editoriais(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html><body>
<div class="title"><h1>Artigo com corpo HTML</h1></div>
<div class="authors"><p>Ana Souza<sup>1</sup></p></div>
<article class="full-article">
  <div class="full-content">
    <div id="textabstract"><p>Resumo curto do artigo.</p></div>
  </div>
</article>
<article class="full-article">
  <div class="full-content">
    <p>Despite the great achievements in controlling <em>Triatoma infestans</em>.<sup>(1)</sup></p>
    <p>More editorial prose about vectors and disease.</p>
    <div class="references">
      <div class="reference"><span class="number">01.</span> Lent H. Revision. 1979.</div>
    </div>
  </div>
</article>
<aside id="references">
  <div class="reference"><span class="number">01.</span> Lent H. Revision. 1979.</div>
</aside>
</body></html>
HTML;

        $resultado = (new ExtrairArtigoRicoDoHtmlMemorias)->extrair($html);

        $this->assertNotNull($resultado);
        $this->assertStringContainsString('Triatoma infestans', (string) $resultado['conteudo_html']);
        $this->assertStringContainsString('<em>', (string) $resultado['conteudo_html']);
        $this->assertStringContainsString('<sup>(1)</sup>', (string) $resultado['conteudo_html']);
        $this->assertStringNotContainsString('Lent H. Revision', (string) $resultado['conteudo_html']);
        $this->assertFalse($resultado['corpo_somente_pdf']);
    }
}
