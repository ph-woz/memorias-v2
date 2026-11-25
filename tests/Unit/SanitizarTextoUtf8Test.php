<?php

namespace Tests\Unit;

use App\Support\Artigos\HeuristicaMetadadosPdfMemorias;
use App\Support\Artigos\MontarArtigoLiteralDoPdf;
use App\Support\Artigos\SanitizarTextoUtf8;
use App\Support\Artigos\ValidarFidelidadeExtracaoPdf;
use PHPUnit\Framework\TestCase;

class SanitizarTextoUtf8Test extends TestCase
{
    public function test_remove_bytes_utf8_invalidos_e_permite_normalizacao(): void
    {
        $bruto = "Corresponding author: a@b.com \xE3\x28 ORCID\nAccepted 08 October 2025\nDespite the great achievements.";
        $limpo = (new SanitizarTextoUtf8)->sanitizar($bruto);

        $this->assertTrue(mb_check_encoding($limpo, 'UTF-8'));
        $norm = (new ValidarFidelidadeExtracaoPdf)->normalizarParaBusca($limpo);
        $this->assertNotSame('', $norm);
        $this->assertStringContainsString('corresponding author', $norm);
    }

    public function test_heuristica_extrai_correspondente_moderno_sem_financial_support(): void
    {
        $texto = <<<'TXT'
RESEARCH ARTICLE
Title of modern article about vectors and disease monitoring strategies in Brazil
Ana Souza
1
/
+
Key words: vectors - disease
doi: 10.1590/0074-02760250190
+ Corresponding author: serviopr@gmail.com | https://orcid.org/0000-0002-0191-8759
Received 24 July 2025
Accepted 08 October 2025
Despite the great achievements in controlling Chagas disease in the region continue.
REFERENCES
1. Autor A. Titulo. 2020.
TXT;

        $resultado = (new HeuristicaMetadadosPdfMemorias)->completar($texto, [
            'autor_correspondente' => 'serviopr@gmail.com',
        ]);

        $corr = (string) $resultado['autor_correspondente'];
        $this->assertStringContainsString('Corresponding author: serviopr@gmail.com', $corr);
        $this->assertStringContainsString('https://orcid.org/0000-0002-0191-8759', $corr);
        $this->assertStringContainsString('Received 24 July 2025', $corr);
        $this->assertStringContainsString('Accepted 08 October 2025', $corr);
        $this->assertStringNotContainsString('How to cite', $corr);
    }

    public function test_montar_nao_falha_com_utf8_invalid_no_pdf(): void
    {
        $lixo = "\xE3\x28\xF0";
        $texto = "Titulo Exato Do Artigo Moderno Sobre Vetores\nAna Souza\n"
            ."Abstract: Resumo literal completo do artigo cientifico sem condensacao.\n"
            .'Introducao comeca aqui com a frase ancora do corpo. Continua o paragrafo original.'
            .str_repeat(' Trecho adicional fiel ao original.', 25)
            ."\nREFERENCES\n1. Autor A. Titulo. 2020.\n2. Autor B. Outro. 2021."
            .$lixo;

        $resultado = (new MontarArtigoLiteralDoPdf)->montar($texto, [
            'titulo' => 'Titulo Exato Do Artigo Moderno Sobre Vetores',
            'resumo' => 'Resumo literal completo do artigo cientifico sem condensacao.',
            'autores' => ['Ana Souza'],
            'marcador_inicio_corpo' => 'Introducao comeca aqui com a frase ancora do corpo.',
            'marcador_inicio_referencias' => 'REFERENCES',
        ]);

        $this->assertStringContainsString('Introducao comeca aqui', $resultado['conteudo_html']);
        $this->assertNotEmpty($resultado['referencias']);
    }
}
