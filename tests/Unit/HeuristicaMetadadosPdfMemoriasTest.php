<?php

namespace Tests\Unit;

use App\Support\Artigos\HeuristicaMetadadosPdfMemorias;
use PHPUnit\Framework\TestCase;

class HeuristicaMetadadosPdfMemoriasTest extends TestCase
{
    public function test_completa_metadados_do_layout_memorias(): void
    {
        $texto = <<<'TXT'
Mem Inst Oswaldo Cruz, Rio de Janeiro, Vol. 116: e210015, 2021 1|10online | memorias.ioc.fiocruz.br
REVIEW
Do the new triatomine species pose new challenges or strategies
for monitoring Chagas disease? An overview from 1979-2021
Jane Costa
1
/
+
, Carolina Dale
1
, Cleber Galvão
2
1
Fundação Oswaldo Cruz-Fiocruz, Instituto Oswaldo Cruz, Laboratório de Biodiversidade Entomológica, Rio de Janeiro, RJ, Brasil
2
Institut de Recherche pour le Développement, Montpellier, France
Chagas disease persists as one of the most important, and yet most neglected, diseases in the world, and several changes in
its epidemiological aspects have been recorded since its discovery. Currently, some of the most relevant changes are related to
the reduction in the incidence of the endemic due to the control of the most important vectors.
Key words: American Trypanosomiasis - vectors - Triatominae subfamily
doi: 10.1590/0 074 - 02760210 015
Financial support: CNPq, FAPESP.
+ Corresponding author: jcosta@ioc.fiocruz.br
https://orcid.org/0000-0001-6664-1394
Received 14 January 2021
Accepted 04 May 2021
Despite the great achievements in controlling Chagas disease, also known as American Trypanosomiasis, major problems remain.
REFERENCES
1. Lent H, Wygodzinsky P. Revision of the Triatominae. 1979.
TXT;

        $resultado = (new HeuristicaMetadadosPdfMemorias)->completar($texto, []);

        $this->assertStringContainsString('Do the new triatomine species pose new challenges', $resultado['titulo']);
        $this->assertSame('10.1590/0074-02760210015', $resultado['doi']);
        $this->assertContains('Jane Costa', $resultado['autores']);
        $this->assertContains('Carolina Dale', $resultado['autores']);
        $this->assertContains('American Trypanosomiasis', $resultado['palavras_chave']);
        $this->assertStringStartsWith('Chagas disease persists', $resultado['resumo']);
        $this->assertStringContainsString('jcosta@ioc.fiocruz.br', (string) $resultado['autor_correspondente']);
        $this->assertStringContainsString('Fundação Oswaldo Cruz-Fiocruz', (string) $resultado['endereco_instituicoes']);
        $this->assertStringContainsString('Despite the great achievements', $resultado['marcador_inicio_corpo']);
        $this->assertSame('REFERENCES', $resultado['marcador_inicio_referencias']);
    }
}
