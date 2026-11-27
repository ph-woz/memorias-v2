<?php

namespace Tests\Unit;

use App\Support\Artigos\MontarHtmlTabelaDeTextoPdf;
use PHPUnit\Framework\TestCase;

class MontarHtmlTabelaDeTextoPdfTest extends TestCase
{
    public function test_nao_trata_cytb_como_genus_nem_and_como_species(): void
    {
        $texto = <<<'TXT'
Genus
Species
Author
Year
Type locality
Distribution
Collection (Type)
Ecotope
DNA sequence
Rhodnius
barretti
Abad-Franch, Palomeque & Monteiro
2013
Puerto Asís (19)
Departament of Putumayo, Colombia
Herman Lent Collection, Instituto Oswaldo Cruz, Rio de Janeiro, Brazil
Attalea butyracea
and
Oenocarpus bataua
-
colombiensis
Mejia, Galvão & Jurberg
1999
Totarco (20)
Coyaima, Colombia
Herman Lent Collection, Instituto Oswaldo Cruz, Rio de Janeiro, Brazil
Attalea butyracea
Cytb, 16S, 18S
micki
Zhao, Galvão & Cai
2021
Santa Cruz, Saavedra (22)
Bolivia
Natural History Museum, UK
-
-
TXT;

        $html = (new MontarHtmlTabelaDeTextoPdf)->dePaginas([$texto]);

        $this->assertStringContainsString('tabela-pdf-scroll', $html);
        $this->assertStringNotContainsString('<td><em>Cytb</em></td>', $html);
        $this->assertDoesNotMatchRegularExpression('/<tr[^>]*>\s*<td[^>]*>\s*(?:<em>)?\s*and\s*</i', $html);
        $this->assertMatchesRegularExpression(
            '/<em>Rhodnius<\/em><\/td><td><em>colombiensis<\/em>/i',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/<em>Rhodnius<\/em><\/td><td><em>micki<\/em>/i',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/colombiensis<\/em><\/td><td>[^<]*Mejia[^<]*<\/td><td>1999<\/td>.*?Cytb, 16S, 18S/is',
            $html,
        );
        $this->assertStringContainsString('Attalea butyracea and Oenocarpus bataua', $html);
    }

    public function test_corrige_letras_espacadas_em_type_locality(): void
    {
        $texto = <<<'TXT'
Genus
Species
Author
Year
Type locality
Distribution
Collection (Type)
Ecotope
DNA sequence
Panstrongylus
lignarius
Walker
2013
Jacu nd á (1)
Pará, Brazil
Herman Lent Collection, Instituto Oswaldo Cruz, Rio de Janeiro, Brazil
Dwellings
-
Triatoma
dimidiata
Latreille
2011
Tole d o (3)
Belize
Herman Lent Collection, Instituto Oswaldo Cruz, Rio de Janeiro, Brazil
Near a cave
Cytb
TXT;

        $html = (new MontarHtmlTabelaDeTextoPdf)->dePaginas([$texto]);

        $this->assertStringContainsString('<td>Jacundá (1)</td>', $html);
        $this->assertStringContainsString('<td>Toledo (3)</td>', $html);
        $this->assertStringNotContainsString('Jacu nd', $html);
        $this->assertStringNotContainsString('Tole d', $html);
        $this->assertStringContainsString('Near a cave', $html);
    }

    public function test_aceita_anos_do_seculo_xix(): void
    {
        $texto = <<<'TXT'
Genus
Species
Author
Year
Type locality
Distribution
Collection (Type)
Ecotope
DNA sequence
Triatoma
dimidiata
Latreille
1811
Toledo (3)
Belize
Herman Lent Collection
Near a cave
Cytb
TXT;

        $html = (new MontarHtmlTabelaDeTextoPdf)->dePaginas([$texto]);

        $this->assertStringContainsString('<td>1811</td>', $html);
        $this->assertStringContainsString('<td>Toledo (3)</td>', $html);
    }
}
