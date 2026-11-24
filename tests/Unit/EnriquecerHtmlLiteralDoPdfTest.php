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
}
