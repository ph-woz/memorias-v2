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
}
