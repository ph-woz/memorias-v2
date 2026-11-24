<?php

namespace Tests\Unit;

use App\Support\Artigos\MontarArtigoLiteralDoPdf;
use PHPUnit\Framework\TestCase;

class MontarArtigoLiteralDoPdfExtrasTest extends TestCase
{
    public function test_desifeniza_corpo_e_nao_parte_referencia_por_ano_entre_parenteses(): void
    {
        $texto = "Titulo Exato Do Artigo\nAna Souza\n"
            ."Accepted 04 May 2021\n"
            ."Despite the great achievements in controlling Cha-\ngas disease, also known as American Trypanosomiasis.\n"
            .'More body text about vectors and monitoring strategies.'
            .str_repeat(' Continuacao fiel do texto.', 20)
            ."\nREFERENCES\n"
            ."1. Lent H, Wygodzinsky P. Revision of the Triatominae. Bull Am Museum Nat Hist. 1979; 163(3): 123-520.\n"
            ."2. Autor B. Species description of N. flavida (Neiva,\n"
            ."1944) and N. flavida (Neiva, 1911). J Vector Ecol. 2018; 43(1): 148-57.\n"
            .'3. Weeks ENI et al. Community-based sampling. 2014.';

        $resultado = (new MontarArtigoLiteralDoPdf)->montar($texto, [
            'titulo' => 'Titulo Exato Do Artigo',
            'resumo' => '',
            'autores' => ['Ana Souza'],
            'marcador_inicio_corpo' => 'Despite the great achievements in controlling',
            'marcador_inicio_referencias' => 'REFERENCES',
        ]);

        $this->assertStringContainsString('controlling Chagas disease', $resultado['conteudo_html']);
        $this->assertStringNotContainsString('Cha- gas', $resultado['conteudo_html']);
        $this->assertCount(3, $resultado['referencias']);
        $this->assertStringContainsString('1944) and N. flavida', $resultado['referencias'][1]);
        $this->assertStringStartsWith('3. Weeks', $resultado['referencias'][2]);
    }
}
