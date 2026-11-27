<?php

namespace Tests\Unit;

use App\Support\Artigos\NormalizarMetadadosEditoriaisPdf;
use PHPUnit\Framework\TestCase;

class NormalizarMetadadosEditoriaisPdfTest extends TestCase
{
    public function test_remove_marcadores_de_afiliacao_e_separa_instituicoes(): void
    {
        $bruto = '1 / + , Carolina Dale 1 , Cleber Galvão 2 , Carlos Eduardo Almeida 3,4 , Jean Pierre Dujardin 5 '
            .'1 Fundação Oswaldo Cruz-Fiocruz, Instituto Oswaldo Cruz, Laboratório de Biodiversidade Entomológica, Rio de Janeiro, RJ, Brasil '
            .'2 Fundação Oswaldo Cruz-Fiocruz, Instituto Oswaldo Cruz, Laboratório Nacional e Internacional de Referência em Taxonomia de Triatomíneos, Rio de Janeiro, RJ, Brasil '
            .'3 Universidade Federal da Bahia, Salvador, BA, Brasil '
            .'4 Universidade Estadual de Campinas, Instituto de Biologia, Campinas, SP, Brasil '
            .'5 Institut de Recherche pour le Développement, Montpellier, France';

        $html = (new NormalizarMetadadosEditoriaisPdf)->enderecoInstituicoes($bruto);

        $this->assertStringNotContainsString('1 / +', $html);
        $this->assertStringNotContainsString('Carolina Dale', $html);
        $this->assertStringNotContainsString('Cleber Galvão', $html);
        $this->assertSame(1, substr_count($html, '<p>'));
        $this->assertSame(4, substr_count($html, '<br>'));
        $this->assertStringContainsString('<sup>1</sup>Fundação Oswaldo Cruz-Fiocruz', $html);
        $this->assertStringContainsString('<sup>5</sup>Institut de Recherche pour le Développement, Montpellier, France</p>', $html);
        $this->assertStringNotContainsString('>1 Fundação', $html);
    }

    public function test_corta_abstract_colado_apos_instituicoes(): void
    {
        $bruto = '1 Fundação Oswaldo Cruz-Fiocruz, Rio de Janeiro, RJ, Brasil '
            .'2 Institut de Recherche pour le Développement, Montpellier, France '
            .'Chagas disease persists as one of the most important diseases. Key words: vectors';

        $html = (new NormalizarMetadadosEditoriaisPdf)->enderecoInstituicoes($bruto);

        $this->assertSame(1, substr_count($html, '<p>'));
        $this->assertSame(1, substr_count($html, '<br>'));
        $this->assertStringContainsString('<sup>1</sup>Fundação', $html);
        $this->assertStringContainsString('<sup>2</sup>Institut', $html);
        $this->assertStringNotContainsString('Chagas disease', $html);
        $this->assertStringNotContainsString('Key words', $html);
    }

    public function test_separa_instituicoes_coladas_sem_espaco_antes_do_numero(): void
    {
        $bruto = '1 Fundação Oswaldo Cruz-Fiocruz, Rio de Janeiro, RJ, Brasil'
            .'2 Institut de Recherche pour le Développement, Montpellier, France'
            .'3 Universidade Federal da Bahia, Salvador, BA, Brasil';

        $html = (new NormalizarMetadadosEditoriaisPdf)->enderecoInstituicoes($bruto);

        $this->assertSame(
            '<p><sup>1</sup>Fundação Oswaldo Cruz-Fiocruz, Rio de Janeiro, RJ, Brasil<br>'
            .'<sup>2</sup>Institut de Recherche pour le Développement, Montpellier, France<br>'
            .'<sup>3</sup>Universidade Federal da Bahia, Salvador, BA, Brasil</p>',
            $html,
        );
    }

    public function test_restaura_quebras_do_autor_correspondente(): void
    {
        $bruto = 'Financial support: CNPq, FAPESP (process no. 2016/08176-9), FAPERJ. '
            .'JC, CEA and CG are CNPq Research Productivity Granted - PQ-2 (303363/2017-7, 306357/2019-4, and 305182/2019-6). '
            .'+ Corresponding author: jcosta@ioc.fiocruz.br '
            .'https://orcid.org/0000-0001-6664-1394 Received 14 January 2021 Accepted 04 May 2021';

        $html = (new NormalizarMetadadosEditoriaisPdf)->autorCorrespondente($bruto);

        $this->assertSame(1, substr_count($html, '<p>'));
        $this->assertGreaterThanOrEqual(4, substr_count($html, '<br>'));
        $this->assertStringContainsString('Financial support: CNPq, FAPESP (process no. 2016/08176-9), FAPERJ.<br>', $html);
        $this->assertStringContainsString('+ Corresponding author: jcosta@ioc.fiocruz.br<br>', $html);
        $this->assertStringContainsString('https://orcid.org/0000-0001-6664-1394<br>', $html);
        $this->assertStringContainsString('Received 14 January 2021<br>', $html);
        $this->assertStringContainsString('Accepted 04 May 2021</p>', $html);
        $this->assertStringNotContainsString('jcosta@ioc.fiocruz.br https://orcid.org', $html);
    }

    public function test_separa_orcid_e_datas_mesmo_sem_espaco(): void
    {
        $bruto = 'Financial support: CNPq, FAPESP (process no. 2016/08176-9), FAPERJ.'
            .'JC, CEA and CG are CNPq Research Productivity Granted - PQ-2 (303363/2017-7, 306357/2019-4, and 305182/2019-6).'
            .'+ Corresponding author: jcosta@ioc.fiocruz.br'
            .'https://orcid.org/0000-0001-6664-1394'
            .'Received 14 January 2021'
            .'Accepted 04 May 2021';

        $html = (new NormalizarMetadadosEditoriaisPdf)->autorCorrespondente($bruto);

        $this->assertStringContainsString('+ Corresponding author: jcosta@ioc.fiocruz.br<br>', $html);
        $this->assertStringContainsString('https://orcid.org/0000-0001-6664-1394<br>', $html);
        $this->assertStringContainsString('Received 14 January 2021<br>', $html);
        $this->assertStringContainsString('Accepted 04 May 2021</p>', $html);
        $this->assertStringNotContainsString('brhttps://orcid', $html);
    }
}
