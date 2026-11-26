<?php

namespace Tests\Unit;

use App\Support\Artigos\PreferirConteudoHtmlOriginal;
use PHPUnit\Framework\TestCase;

class PreferirConteudoHtmlOriginalTest extends TestCase
{
    public function test_mantem_corpo_do_pdf_e_mescla_metadados_do_portal(): void
    {
        $textoPdf = 'Despite the great achievements in controlling Chagas disease, also known as American Trypanosomiasis, '
            .'major problems remain. The most effective action is the elimination of Triatoma infestans vectors. '
            .str_repeat('Continuacao fiel do texto original. ', 30)
            .' Laboratório de Biologia, Fundação Oswaldo Cruz-Fiocruz, Rio de Janeiro, RJ, Brasil'
            .' Corresponding author: jcosta@ioc.fiocruz.br';

        $pdfCorpo = '<p><strong>Triatominae until 1979</strong></p>'
            .'<p>Despite the great achievements in controlling Chagas disease, also known as American Trypanosomiasis, major problems remain.</p>';

        $pdf = [
            'conteudo_html' => $pdfCorpo,
            'resumo' => 'Resumo curto do pdf',
            'endereco_instituicoes' => 'Instituicao do PDF',
            'autor_correspondente' => 'Autor PDF',
            'referencias' => ['1. Ref A'],
            'doi' => '10.1590/0074-02760210015',
        ];

        $html = [
            'conteudo_html' => '<p>Despite the great achievements in controlling Chagas disease, also known as American Trypanosomiasis, major problems remain.<sup>(2)</sup></p>'
                .'<p>The most effective action is the elimination of <em>Triatoma infestans</em> vectors.</p>'
                .'<p>Triatominae until 1979</p>'
                .str_repeat('<p>Continuacao fiel do texto original.</p>', 20),
            'resumo' => '<p>Despite the great achievements in controlling Chagas disease abstract text for validation against pdf source document here.</p>',
            'endereco_instituicoes' => '<p><sup>1</sup> Laboratório de Biologia, Fundação Oswaldo Cruz-Fiocruz, Rio de Janeiro, RJ, Brasil</p>',
            'autor_correspondente' => '<p>+ Corresponding author: <em>jcosta@ioc.fiocruz.br</em></p>',
            'referencias' => ['1 Ref A', '2 Ref B', '3 Ref C'],
            'fonte' => 'scielo',
            'html_origem_url' => 'https://www.scielo.br/example',
            'palavras_chave' => ['vectors'],
        ];

        $resultado = (new PreferirConteudoHtmlOriginal)->preferir($pdf, $html, $textoPdf);

        $this->assertSame($pdfCorpo, $resultado['conteudo_html']);
        $this->assertArrayNotHasKey('fonte_conteudo', $resultado);
        $this->assertSame('scielo', $resultado['fonte_metadados']);
        $this->assertStringContainsString('<strong>Triatominae until 1979</strong>', $resultado['conteudo_html']);
        $this->assertStringNotContainsString('<p>Triatominae until 1979</p>', $resultado['conteudo_html']);
        $this->assertStringContainsString('<p>', (string) $resultado['resumo']);
        $this->assertStringContainsString('<em>jcosta@ioc.fiocruz.br</em>', (string) $resultado['autor_correspondente']);
        $this->assertStringContainsString('Fundação Oswaldo Cruz-Fiocruz', strip_tags((string) $resultado['endereco_instituicoes']));
        $this->assertCount(3, $resultado['referencias']);
        $this->assertSame('https://www.scielo.br/example', $resultado['html_origem_url']);
    }

    public function test_mantem_pdf_quando_html_nao_bate_com_o_inicio(): void
    {
        $textoPdf = str_repeat('Texto original do artigo cientifico sobre vetores. ', 40);

        $pdf = [
            'conteudo_html' => '<p>'.str_repeat('Texto original do artigo cientifico sobre vetores. ', 20).'</p>',
        ];

        $html = [
            'conteudo_html' => '<p>'.str_repeat('Conteudo totalmente diferente sem sobreposicao com o pdf. ', 20).'</p>',
            'fonte' => 'scielo',
        ];

        $resultado = (new PreferirConteudoHtmlOriginal)->preferir($pdf, $html, $textoPdf);

        $this->assertArrayNotHasKey('fonte_conteudo', $resultado);
        $this->assertSame($pdf['conteudo_html'], $resultado['conteudo_html']);
    }

    public function test_mescla_autor_correspondente_mesmo_sem_corpo_html(): void
    {
        $bloco = 'Financial support: CNPq, FAPESP (process no. 2016/08176-9), FAPERJ. '
            .'JC, CEA and CG are CNPq Research Productivity Granted - PQ-2 (303363/2017-7, 306357/2019-4, and 305182/2019-6). '
            .'+ Corresponding author: jcosta@ioc.fiocruz.br '
            .'https://orcid.org/0000-0001-6664-1394 '
            .'Received 14 January 2021 '
            .'Accepted 04 May 2021';

        $textoPdf = 'Despite the great achievements in controlling Chagas disease. '.$bloco;

        $pdf = [
            'conteudo_html' => '<p>Despite the great achievements in controlling Chagas disease.</p>',
            'autor_correspondente' => 'jcosta@ioc.fiocruz.br',
        ];

        $html = [
            'conteudo_html' => '',
            'autor_correspondente' => '<p>'.$bloco.'</p>',
            'fonte' => 'memorias',
        ];

        $resultado = (new PreferirConteudoHtmlOriginal)->preferir($pdf, $html, $textoPdf);

        $this->assertArrayNotHasKey('fonte_conteudo', $resultado);
        $this->assertStringContainsString('Financial support: CNPq, FAPESP (process no. 2016/08176-9), FAPERJ.', (string) $resultado['autor_correspondente']);
        $this->assertStringContainsString('Accepted 04 May 2021', (string) $resultado['autor_correspondente']);
        $this->assertSame($pdf['conteudo_html'], $resultado['conteudo_html']);
    }
}
