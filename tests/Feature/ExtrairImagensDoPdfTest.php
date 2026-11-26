<?php

namespace Tests\Feature;

use App\Support\Artigos\ExtrairImagensDoPdf;
use Illuminate\Support\Facades\Storage;
use Imagick;
use ImagickDraw;
use ImagickPixel;
use Tests\TestCase;

class ExtrairImagensDoPdfTest extends TestCase
{
    public function test_pdf_multipagina_sem_figuras_nao_gera_prints_de_pagina(): void
    {
        if (! class_exists(Imagick::class)) {
            $this->markTestSkipped('Extensão Imagick indisponível.');
        }

        Storage::fake('public');

        $caminho = 'artigos/importacoes/sem-figuras.pdf';
        Storage::disk('public')->put($caminho, $this->pdfMultipaginaSomenteTexto(4));

        $imagens = app(ExtrairImagensDoPdf::class)->deCaminhoPublico($caminho);

        $this->assertSame([], $imagens);
        $this->assertNenhumArquivoDePrintDePagina();
    }

    public function test_pdf_de_demo_nao_gera_prints_de_pagina(): void
    {
        $demo = base_path('public/demo-artigo.pdf');

        if (! is_file($demo)) {
            $this->markTestSkipped('public/demo-artigo.pdf ausente.');
        }

        Storage::fake('public');

        $caminho = 'artigos/importacoes/demo-artigo.pdf';
        Storage::disk('public')->put($caminho, (string) file_get_contents($demo));

        $imagens = app(ExtrairImagensDoPdf::class)->deCaminhoPublico($caminho);

        foreach ($imagens as $imagem) {
            $this->assertDoesNotMatchRegularExpression('/pagina-\d+/i', (string) ($imagem['path'] ?? ''));
            $this->assertDoesNotMatchRegularExpression('/pagina-\d+/i', (string) ($imagem['url'] ?? ''));
            $this->assertDoesNotMatchRegularExpression('/pagina-\d+/i', (string) ($imagem['alt'] ?? ''));
        }

        $this->assertNenhumArquivoDePrintDePagina();
    }

    private function assertNenhumArquivoDePrintDePagina(): void
    {
        foreach (Storage::disk('public')->allFiles('artigos/conteudo') as $arquivo) {
            $this->assertDoesNotMatchRegularExpression(
                '/pagina-\d+\.(jpe?g|png|webp)$/i',
                $arquivo,
                "Print de página gerado indevidamente: {$arquivo}",
            );
        }
    }

    private function pdfMultipaginaSomenteTexto(int $paginas): string
    {
        $pdf = new Imagick;
        $pdf->setResolution(72, 72);

        for ($i = 1; $i <= $paginas; $i++) {
            $page = new Imagick;
            $page->newImage(612, 792, new ImagickPixel('white'));
            $page->setImageFormat('pdf');

            $draw = new ImagickDraw;
            $draw->setFillColor(new ImagickPixel('black'));
            $draw->setFontSize(16);
            $page->annotateImage(
                $draw,
                40,
                80,
                0,
                "Page {$i}: Valid species and Triatoma infestans text body without figures.",
            );

            $pdf->addImage($page);
            $page->clear();
            $page->destroy();
        }

        $pdf->setImageFormat('pdf');
        $blob = $pdf->getImagesBlob();
        $pdf->clear();
        $pdf->destroy();

        return $blob;
    }
}
