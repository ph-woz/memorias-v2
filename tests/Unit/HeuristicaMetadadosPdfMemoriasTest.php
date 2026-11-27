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
        $correspondente = (string) $resultado['autor_correspondente'];
        $this->assertStringStartsWith('Financial support:', $correspondente);
        $this->assertStringContainsString('jcosta@ioc.fiocruz.br', $correspondente);
        $this->assertStringContainsString('https://orcid.org/0000-0001-6664-1394', $correspondente);
        $this->assertStringContainsString('Received 14 January 2021', $correspondente);
        $this->assertStringContainsString('Accepted 04 May 2021', $correspondente);
        $instituicoes = (string) $resultado['endereco_instituicoes'];
        $this->assertStringContainsString('Fundação Oswaldo Cruz-Fiocruz', $instituicoes);
        $this->assertStringContainsString('Institut de Recherche pour le Développement', $instituicoes);
        $this->assertStringNotContainsString('1 / +', $instituicoes);
        $this->assertStringNotContainsString('Carolina Dale', $instituicoes);
        $this->assertStringContainsString('Despite the great achievements', $resultado['marcador_inicio_corpo']);
        $this->assertSame('REFERENCES', $resultado['marcador_inicio_referencias']);
    }

    public function test_substitui_email_isolado_pelo_bloco_editorial_completo(): void
    {
        $texto = <<<'TXT'
doi: 10.1590/0074-02760210015
Financial support: CNPq, FAPESP (process no. 2016/08176-9), FAPERJ.
JC, CEA and CG are CNPq Research Productivity Granted - PQ-2 (303363/2017-7, 306357/2019-4, and 305182/2019-6).
+ Corresponding author: jcosta@ioc.fiocruz.br
https://orcid.org/0000-0001-6664-1394
Received 14 January 2021
Accepted 04 May 2021
Despite the great achievements in controlling Chagas disease.
TXT;

        $resultado = (new HeuristicaMetadadosPdfMemorias)->completar($texto, [
            'autor_correspondente' => 'jcosta@ioc.fiocruz.br',
        ]);

        $correspondente = (string) $resultado['autor_correspondente'];
        $this->assertStringContainsString('Financial support: CNPq, FAPESP (process no. 2016/08176-9), FAPERJ.', $correspondente);
        $this->assertStringContainsString('PQ-2 (303363/2017-7, 306357/2019-4, and 305182/2019-6)', $correspondente);
        $this->assertStringContainsString('+ Corresponding author: jcosta@ioc.fiocruz.br', $correspondente);
        $this->assertStringContainsString('https://orcid.org/0000-0001-6664-1394', $correspondente);
        $this->assertStringContainsString('Received 14 January 2021', $correspondente);
        $this->assertStringContainsString('Accepted 04 May 2021', $correspondente);
    }

    public function test_completa_abstract_estruturado_e_corpo_apos_key_words(): void
    {
        $texto = <<<'TXT'
Mem Inst Oswaldo Cruz, Rio de Janeiro, Vol. 121: e250109, 2026 1|17online | memorias.ioc.fiocruz.br
RESEARCH ARTICLE
Extracellular vesicles isolated from the plasma of COVID-19 and sepsis
patients: characterisation and association with clinical outcomes
Ana Souza
1
/
+
, Bruno Lima
2
1
Universidade Federal de São Paulo, São Paulo, SP, Brasil
2
Fundação Oswaldo Cruz-Fiocruz, Belo Horizonte, MG, Brasil
BACKGROUND
 Extracellular vesicles (EVs) are involved in the pathogenesis of SARS-CoV-2 infection.
OBJECTIVES We analysed concentration and size of EVs in plasma samples.
METHODS Plasma samples from COVID-19 patients were analysed by NTA and flow cytometry.
FINDINGS NTA showed an increased concentration of microparticles in patients.
MAIN CONCLUSIONS Our findings suggest that patients EVs likely harboured viral components.
Key words: extracellular vesicles - SARS-CoV-2 - COVID-19 - sepsis
Since 2003, coronaviruses have been the cause of serious and fatal diseases in humans worldwide.
doi: 10.1590/0074-02760250109
Financial support: FAPESP.
+ Corresponding authors: ana@example.com
Received 29 April 2025
Accepted 26 September 2025
How to cite: Souza A, Lima B. Extracellular vesicles. Mem Inst Oswaldo Cruz. 2026; 121: e250109.
Handling editor: Adeilton Alves Brandão
More body continues after the sidebar noise with clinical outcomes discussed.
REFERENCES
1. Tay MZ. The trinity of COVID-19. 2020.
TXT;

        $resultado = (new HeuristicaMetadadosPdfMemorias)->completar($texto, []);

        $this->assertStringContainsString('Extracellular vesicles isolated from the plasma', $resultado['titulo']);
        $this->assertSame('10.1590/0074-02760250109', $resultado['doi']);
        $this->assertStringStartsWith('BACKGROUND', $resultado['resumo']);
        $this->assertStringContainsString('MAIN CONCLUSIONS', $resultado['resumo']);
        $this->assertContains('extracellular vesicles', $resultado['palavras_chave']);
        $this->assertStringStartsWith('Since 2003, coronaviruses', $resultado['marcador_inicio_corpo']);
        $this->assertStringNotContainsString('How to cite', $resultado['marcador_inicio_corpo']);
    }
}
