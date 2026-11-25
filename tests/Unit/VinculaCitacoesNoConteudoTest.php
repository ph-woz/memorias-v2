<?php

namespace Tests\Unit;

use App\Support\Artigos\VinculaCitacoesNoConteudo;
use PHPUnit\Framework\TestCase;

class VinculaCitacoesNoConteudoTest extends TestCase
{
    public function test_envolve_superscript_com_numeros_em_botao_clicavel(): void
    {
        $html = '<p>Texto<sup>30, 31</sup> continua.</p>';

        $resultado = (new VinculaCitacoesNoConteudo)($html);

        $this->assertStringContainsString('class="citacao-ref"', $resultado);
        $this->assertStringContainsString('data-refs="30,31"', $resultado);
        $this->assertStringContainsString('<sup>30, 31</sup>', $resultado);
    }

    public function test_nao_altera_superscript_sem_numeros(): void
    {
        $html = '<p>Nota<sup>*</sup></p>';

        $resultado = (new VinculaCitacoesNoConteudo)($html);

        $this->assertSame($html, $resultado);
    }

    public function test_nao_usa_anos_bibliograficos_como_referencia(): void
    {
        $html = '<p>Brazil.<sup>(77, 1977, 2009, 9, 1, 123, 8, 78, 2017, 176, 364, 72)</sup> However,</p>';

        $resultado = (new VinculaCitacoesNoConteudo)($html);

        $this->assertStringContainsString('data-refs="77,78"', $resultado);
        $this->assertStringContainsString('<sup>77, 78</sup>', $resultado);
        $this->assertStringNotContainsString('1977', $resultado);
        $this->assertStringNotContainsString('data-refs="77,1977', $resultado);
    }

    public function test_nao_aninha_botao_quando_ja_vinculado(): void
    {
        $html = '<p>Texto<button type="button" class="citacao-ref" data-refs="2" aria-label="Abrir referência 2"><sup>2</sup></button> fim.</p>';

        $resultado = (new VinculaCitacoesNoConteudo)($html);

        $this->assertSame(1, substr_count($resultado, 'citacao-ref'));
        $this->assertSame($html, $resultado);
    }
}
