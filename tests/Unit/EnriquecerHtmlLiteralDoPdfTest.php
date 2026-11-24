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
}
