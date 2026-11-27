<?php

namespace Tests\Unit;

use App\Support\Artigos\RepararEspacamentoLetrasDeTextoPdf;
use PHPUnit\Framework\TestCase;

class RepararEspacamentoLetrasDeTextoPdfTest extends TestCase
{
    public function test_corrige_localidades_com_letras_espacadas(): void
    {
        $reparar = new RepararEspacamentoLetrasDeTextoPdf;

        $this->assertSame('Jacundá (1)', $reparar->reparar('Jacu nd á (1)'));
        $this->assertSame('Toledo (3)', $reparar->reparar('Tole d o (3)'));
        $this->assertSame('Arica (10)', $reparar->reparar('A r ica (10)'));
        $this->assertSame('Colima (31)', $reparar->reparar('Col i m a (31)'));
        $this->assertSame('Totarco (20)', $reparar->reparar('Tot a r c o (2 0)'));
    }

    public function test_nao_altera_frases_legitimas(): void
    {
        $reparar = new RepararEspacamentoLetrasDeTextoPdf;

        $this->assertSame('Near a cave', $reparar->reparar('Near a cave'));
        $this->assertSame('Rio de Janeiro, Brazil', $reparar->reparar('Rio de Janeiro, Brazil'));
        $this->assertSame('Puerto Asís (19)', $reparar->reparar('Puerto Asís (19)'));
        $this->assertSame(
            'Attalea butyracea and Oenocarpus bataua',
            $reparar->reparar('Attalea butyracea and Oenocarpus bataua'),
        );
    }

    public function test_repara_celulas_html_da_tabela(): void
    {
        $html = '<table><tr><td>Jacu nd á (1)</td><td>Tole d o (3)</td><td>Near a cave</td></tr></table>';
        $reparado = (new RepararEspacamentoLetrasDeTextoPdf)->repararHtmlTabela($html);

        $this->assertStringContainsString('<td>Jacundá (1)</td>', $reparado);
        $this->assertStringContainsString('<td>Toledo (3)</td>', $reparado);
        $this->assertStringContainsString('<td>Near a cave</td>', $reparado);
        $this->assertTrue((new RepararEspacamentoLetrasDeTextoPdf)->pareceEspacamentoArtificial('Jacu nd á (1)'));
        $this->assertFalse((new RepararEspacamentoLetrasDeTextoPdf)->pareceEspacamentoArtificial('Jacundá (1)'));
    }

    public function test_repara_prosa_com_localidade_espacada_e_sup(): void
    {
        $html = '<p>Frias, Henry &amp; González 1998 A r ica <sup>(10)</sup> Caleta Vitor, Chile</p>'
            .'<p>Mejia, Galvão &amp; Jurberg 1999 Tot a r c o (2 0) Coyaima, Colombia</p>'
            .'<p>Martínez, Carcavallo &amp; Pelaez 1984 Col i m a <sup>(31)</sup> Jalisco, Mexico</p>';

        $reparado = (new RepararEspacamentoLetrasDeTextoPdf)->repararHtml($html);

        $this->assertStringContainsString('Arica', $reparado);
        $this->assertStringContainsString('Totarco (20)', $reparado);
        $this->assertStringContainsString('Colima', $reparado);
        $this->assertStringNotContainsString('A r ica', $reparado);
        $this->assertStringNotContainsString('Tot a r', $reparado);
        $this->assertStringNotContainsString('Col i m', $reparado);
        $this->assertStringContainsString('Near a cave', (new RepararEspacamentoLetrasDeTextoPdf)->repararHtml('<p>Near a cave</p>'));
    }
}
