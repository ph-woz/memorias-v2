<?php

namespace Tests\Unit;

use App\Support\Artigos\RefinarMetadadosExtracaoPdf;
use PHPUnit\Framework\TestCase;

class RefinarMetadadosExtracaoPdfTest extends TestCase
{
    public function test_extrai_titulo_e_resumo_sem_cabecalho_editorial(): void
    {
        $texto = <<<'TXT'
Mem Inst Oswaldo Cruz, Rio de Janeiro, Vol. 116: e210015, 2021 1|10online | memorias.ioc.fiocruz.br
REVIEW
Do the new triatomine species pose new challenges or strategies
for monitoring Chagas disease? An overview from 1979-2021
Jane Costa
1
Fundação Oswaldo Cruz-Fiocruz, Instituto Oswaldo Cruz, Rio de Janeiro, RJ, Brasil
5
Institut de Recherche pour le Développement, Montpellier, France
Chagas disease persists as one of the most important, and yet most neglected, diseases in the world, and several changes in
its epidemiological aspects have been recorded since its discovery. Currently, some of the most relevant changes are related to
the reduction in the incidence of the endemic due to the control of the most important vectors.
TXT;

        $resultado = (new RefinarMetadadosExtracaoPdf)->refinar($texto, [
            'titulo' => 'Janeiro, Vol. 116: e210015, 2021 1|10online | memorias.ioc.fiocruz.br REVIEW Do the new triatomine',
            'resumo' => "5\nInstitut de Recherche pour le Développement, Montpellier, France\nChagas disease persists as one of the most important, and yet most neglected, diseases in the world, and several changes in its epidemiological aspects have been recorded since its discovery.",
        ]);

        $this->assertStringContainsString('Do the new triatomine species pose new challenges', $resultado['titulo']);
        $this->assertStringNotContainsString('memorias.ioc', $resultado['titulo']);
        $this->assertStringNotContainsString('Vol. 116', $resultado['titulo']);
        $this->assertStringStartsWith('Chagas disease persists', $resultado['resumo']);
        $this->assertStringNotContainsString('Institut de Recherche', $resultado['resumo']);
    }
}
