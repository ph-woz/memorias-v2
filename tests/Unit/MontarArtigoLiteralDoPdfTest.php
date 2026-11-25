<?php

namespace Tests\Unit;

use App\Support\Artigos\MontarArtigoLiteralDoPdf;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MontarArtigoLiteralDoPdfTest extends TestCase
{
    public function test_monta_conteudo_e_referencias_por_fatiamento_literal(): void
    {
        $texto = "Titulo Exato Do Artigo\nAna Souza\nAbstract: Este e o resumo literal completo do artigo sem condensacao.\n"
            .'Introducao comeca aqui com a frase ancora do corpo. Continua o paragrafo original, sem mudancas.'
            .str_repeat(' Trecho adicional fiel ao original.', 20)
            ."\nMais um paragrafo do autor.\nREFERENCES\n1. Autor A. Titulo. 2020.\n2. Autor B. Outro titulo. 2021.";

        $resultado = (new MontarArtigoLiteralDoPdf)->montar($texto, [
            'titulo' => 'Titulo Exato Do Artigo',
            'resumo' => 'Este e o resumo literal completo do artigo sem condensacao.',
            'autores' => ['Ana Souza'],
            'marcador_inicio_corpo' => 'Introducao comeca aqui com a frase ancora do corpo.',
            'marcador_inicio_referencias' => 'REFERENCES',
        ]);

        $this->assertStringContainsString('Introducao comeca aqui com a frase ancora do corpo.', $resultado['conteudo_html']);
        $this->assertStringContainsString('Continua o paragrafo original, sem mudancas.', $resultado['conteudo_html']);
        $this->assertStringNotContainsString('REFERENCES', $resultado['conteudo_html']);
        $this->assertSame([
            'Autor A. Titulo. 2020.',
            'Autor B. Outro titulo. 2021.',
        ], $resultado['referencias']);
    }

    public function test_rejeita_campo_reescrito_que_nao_existe_no_pdf(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Fidelidade violada');

        (new MontarArtigoLiteralDoPdf)->montar(
            str_repeat('Texto original do artigo cientifico. ', 40),
            [
                'titulo' => 'Titulo inventado pela IA',
                'resumo' => '',
                'autores' => [],
                'marcador_inicio_corpo' => 'Texto original do artigo cientifico.',
                'marcador_inicio_referencias' => '',
            ],
        );
    }

    public function test_ancora_marcador_aproximado_e_preenche_conteudo(): void
    {
        $texto = "Titulo Exato Do Artigo\nAna Souza\nAbstract: Este e o resumo literal completo do artigo sem condensacao.\n"
            ."INTRODUCTION\nO corpo real comeca nesta secao e continua com o texto do autor."
            .str_repeat(' Trecho adicional fiel ao original.', 20)
            ."\nREFERENCES\n1. Autor A. Titulo. 2020.\n2. Autor B. Outro titulo. 2021.";

        $resultado = (new MontarArtigoLiteralDoPdf)->montar($texto, [
            'titulo' => 'Titulo Exato Do Artigo',
            'resumo' => 'Este e o resumo literal completo do artigo sem condensacao.',
            'autores' => ['Ana Souza'],
            // Marcador ligeiramente diferente do PDF (IA "quase" acertou).
            'marcador_inicio_corpo' => 'INTRODUCTION O corpo real comeca nesta secao reinventada',
            'marcador_inicio_referencias' => 'REFERENCES inventadas',
        ]);

        $this->assertStringContainsString('INTRODUCTION', $resultado['conteudo_html']);
        $this->assertStringContainsString('O corpo real comeca nesta secao', $resultado['conteudo_html']);
        $this->assertNotEmpty($resultado['referencias']);
    }
}
