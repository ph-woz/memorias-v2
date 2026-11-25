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
        $this->assertStringStartsWith('Autor A.', $resultado['referencias'][0]);
        $this->assertStringNotContainsString('REFERENCES', $resultado['referencias'][0]);
        $this->assertDoesNotMatchRegularExpression('/^\d/', $resultado['referencias'][0]);
    }

    public function test_preserva_citacoes_multiplas_sem_anos_bibliograficos(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html><body>
<div class="title"><h1>Artigo com citacoes</h1></div>
<article class="full-article">
  <div class="full-content">
    <p><em>Rhodnius amazonicus</em> Almeida, Santos &amp; Sposina, 1973 and <em>R. zeledoni</em> Jurberg Rocha &amp; Galvão, 2009 are triatomines of rare occurrence, recorded in the north and northeastern Brazil.<sup>(<a href="/reference77" id="77" class="click-reference">77</a>, <a href="/reference78" id="78" class="click-reference">78</a>)</sup></sup> However, they still need confirmation.</p>
  </div>
</article>
</body></html>
HTML;

        $resultado = (new ExtrairArtigoRicoDoHtmlMemorias)->extrair($html);

        $this->assertNotNull($resultado);
        $this->assertStringContainsString(
            'northeastern Brazil.<sup>(77, 78)</sup> However,',
            (string) $resultado['conteudo_html'],
        );
        $this->assertStringNotContainsString('1977', (string) $resultado['conteudo_html']);
        $this->assertStringNotContainsString('<sup>(77)</sup>', (string) $resultado['conteudo_html']);
    }

    public function test_extrai_bloco_completo_do_autor_correspondente(): void
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html><body>
<div class="title"><h1>Artigo correspondente completo</h1></div>
<article class="full-article"><div class="full-content"><div id="textabstract"><p>Resumo.</p></div></div></article>
<div id="author-reference" class="author-reference">
  <p>Financial support: CNPq, FAPESP (process no. 2016/08176-9), FAPERJ. <br />JC, CEA and CG are CNPq Research Productivity Granted - PQ-2 (303363/2017-7, 306357/2019-4, and 305182/2019-6). <br /><a href="#top">+</a> Corresponding author: <a href="mailto:jcosta@ioc.fiocruz.br">jcosta@ioc.fiocruz.br</a><br /> <img src="/images/orcid_16x16.gif" alt="ORCID" /> https://orcid.org/0000-0001-6664-1394 <br />Received 14 January 2021<br />Accepted 04 May 2021</p>
  <a id="back" href="#system" class="return-top"><i class="fa fa-angle-up"></i></a>
</div>
</body></html>
HTML;

        $resultado = (new ExtrairArtigoRicoDoHtmlMemorias)->extrair($html);

        $this->assertNotNull($resultado);
        $correspondente = (string) $resultado['autor_correspondente'];
        $this->assertStringContainsString('Financial support: CNPq, FAPESP (process no. 2016/08176-9), FAPERJ.', $correspondente);
        $this->assertStringContainsString('PQ-2 (303363/2017-7, 306357/2019-4, and 305182/2019-6)', $correspondente);
        $this->assertStringContainsString('jcosta@ioc.fiocruz.br', $correspondente);
        $this->assertStringContainsString('https://orcid.org/0000-0001-6664-1394', $correspondente);
        $this->assertStringContainsString('Received 14 January 2021', $correspondente);
        $this->assertStringContainsString('Accepted 04 May 2021', $correspondente);
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
