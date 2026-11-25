<?php

namespace Tests\Unit;

use App\Support\Artigos\NormalizarCitacoesNoHtml;
use PHPUnit\Framework\TestCase;

class NormalizarCitacoesNoHtmlTest extends TestCase
{
    public function test_preserva_citacao_multipla_do_portal_memorias(): void
    {
        $html = '<p><em>Rhodnius amazonicus</em> Almeida, Santos &amp; Sposina, 1973 and <em>R. zeledoni</em> '
            .'Jurberg Rocha &amp; Galvão, 2009 are triatomines of rare occurrence, recorded in the north and '
            .'northeastern Brazil.<sup>(<a href="/reference77" id="77" class="click-reference">77</a>, '
            .'<a href="/reference78" id="78" class="click-reference">78</a>)</sup></sup> However,</p>';

        $resultado = (new NormalizarCitacoesNoHtml)->normalizar($html);

        $this->assertStringContainsString(
            'northeastern Brazil.<sup>(77, 78)</sup> However,',
            $resultado,
        );
        $this->assertStringNotContainsString('1977', $resultado);
        $this->assertStringNotContainsString('<sup>(77)</sup>', $resultado);
    }

    public function test_remove_anos_e_paginas_colados_na_citacao(): void
    {
        $html = '<p>northeastern Brazil.<sup>(77, 1977, 2009, 9, 1, 123, 8, 78, 2017, 176, 364, 72)</sup> However,</p>';

        $resultado = (new NormalizarCitacoesNoHtml)->normalizar($html);

        $this->assertStringContainsString('<sup>(77, 78)</sup>', $resultado);
        $this->assertStringNotContainsString('1977', $resultado);
        $this->assertStringNotContainsString('2017', $resultado);
        $this->assertStringNotContainsString('364', $resultado);
    }

    public function test_nao_transforma_ano_taxonomico_em_citacao(): void
    {
        $html = '<p>related to <em>T. nigromaculata</em> (Stål, 1859). More text.</p>';

        $resultado = (new NormalizarCitacoesNoHtml)->normalizar($html);

        $this->assertSame($html, $resultado);
    }
}
