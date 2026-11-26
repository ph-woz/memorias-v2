<?php

namespace Tests\Unit;

use App\Support\Artigos\ExtrairTextoTabelasDoPdf;
use App\Support\Artigos\MontarHtmlTabelaDeTextoPdf;
use Tests\TestCase;

class MontarHtmlTabelaDeTextoPdfTest extends TestCase
{
    public function test_monta_table_html_a_partir_do_texto_das_paginas_landscape(): void
    {
        $fixture = base_path('tests/Fixtures/Artigos/triatomine-landscape-table.pdf');

        if (! is_file($fixture)) {
            $this->markTestSkipped('Fixture PDF ausente.');
        }

        $textos = (new ExtrairTextoTabelasDoPdf)->deArquivo($fixture);

        $this->assertNotEmpty($textos);

        $html = (new MontarHtmlTabelaDeTextoPdf)->dePaginas($textos);

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('<caption>TABLE I</caption>', $html);
        $this->assertStringContainsString('<th>Type locality</th>', $html);
        $this->assertStringContainsString('<th>DNA sequence</th>', $html);
        $this->assertStringContainsString('<em>Alberprosenia</em>', $html);
        $this->assertStringContainsString('<em>malheiroi</em>', $html);
        $this->assertStringContainsString('1987', $html);
        $this->assertGreaterThanOrEqual(10, substr_count($html, '<tr>'));
    }
}
