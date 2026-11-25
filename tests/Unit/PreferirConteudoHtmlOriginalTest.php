<?php

namespace Tests\Unit;

use App\Support\Artigos\PreferirConteudoHtmlOriginal;
use PHPUnit\Framework\TestCase;

class PreferirConteudoHtmlOriginalTest extends TestCase
{
    public function test_prefere_html_publicado_quando_compativel_com_pdf(): void
    {
        $textoPdf = 'Despite the great achievements in controlling Chagas disease, also known as American Trypanosomiasis, '
            .'major problems remain. The most effective action is the elimination of Triatoma infestans vectors. '
            .str_repeat('Continuacao fiel do texto original. ', 30);

        $pdf = [
            'conteudo_html' => '<p>Despite the great achievements in controlling Chagas disease, also known as American Trypanosomiasis, major problems remain.</p>',
            'resumo' => 'Resumo curto do pdf',
            'endereco_instituicoes' => 'Instituicao do PDF',
            'autor_correspondente' => 'Autor PDF',
            'referencias' => ['1. Ref A'],
            'doi' => '10.1590/0074-02760210015',
        ];

        $html = [
            'conteudo_html' => '<p>Despite the great achievements in controlling Chagas disease, also known as American Trypanosomiasis, major problems remain.<sup>(2)</sup></p>'
                .'<p>The most effective action is the elimination of <em>Triatoma infestans</em> vectors.</p>'
                .str_repeat('<p>Continuacao fiel do texto original.</p>', 20),
            'resumo' => '<p>Despite the great achievements in controlling Chagas disease abstract text for validation against pdf source document here.</p>',
            'endereco_instituicoes' => '<p><sup>1</sup> Laboratório de Biologia, Fundação Oswaldo Cruz-Fiocruz, Rio de Janeiro, RJ, Brasil</p>',
            'autor_correspondente' => '<p>+ Corresponding author: <em>jcosta@ioc.fiocruz.br</em></p>',
            'referencias' => ['1 Ref A', '2 Ref B', '3 Ref C'],
            'fonte' => 'scielo',
            'html_origem_url' => 'https://www.scielo.br/example',
            'palavras_chave' => ['vectors'],
        ];

        $textoPdf .= ' Laboratório de Biologia, Fundação Oswaldo Cruz-Fiocruz, Rio de Janeiro, RJ, Brasil'
            .' Corresponding author: jcosta@ioc.fiocruz.br';

        $resultado = (new PreferirConteudoHtmlOriginal)->preferir($pdf, $html, $textoPdf);

        $this->assertSame('scielo', $resultado['fonte_conteudo']);
        $this->assertStringContainsString('<em>Triatoma infestans</em>', $resultado['conteudo_html']);
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
}
