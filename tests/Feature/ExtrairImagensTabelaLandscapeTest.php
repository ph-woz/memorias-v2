<?php

namespace Tests\Feature;

use App\Support\Artigos\EnriquecerHtmlLiteralDoPdf;
use App\Support\Artigos\ExtrairImagensDoPdf;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExtrairImagensTabelaLandscapeTest extends TestCase
{
    public function test_extrai_rasters_de_tabela_landscape_do_pdf_fixture(): void
    {
        if (! class_exists(\Imagick::class)) {
            $this->markTestSkipped('Imagick não disponível.');
        }

        $fixture = base_path('tests/Fixtures/Artigos/triatomine-landscape-table.pdf');

        if (! is_file($fixture)) {
            $this->markTestSkipped('Fixture PDF ausente.');
        }

        Storage::fake('public');

        $caminho = 'artigos/importacoes/triatomine-landscape-table.pdf';
        Storage::disk('public')->put($caminho, (string) file_get_contents($fixture));

        $imagens = (new ExtrairImagensDoPdf)->deCaminhoPublico($caminho);
        $urlsTabela = array_values(array_filter(
            array_map(fn (array $imagem): string => (string) ($imagem['url'] ?? ''), $imagens),
            fn (string $url): bool => str_contains($url, 'tabela-pagina-'),
        ));

        $this->assertNotEmpty($urlsTabela, 'Esperava ao menos um raster tabela-pagina-* da TABLE I landscape.');

        foreach ($imagens as $imagem) {
            $chave = ($imagem['path'] ?? '').' '.($imagem['url'] ?? '').' '.($imagem['alt'] ?? '');
            $this->assertDoesNotMatchRegularExpression('/(?<!tabela-)pagina-\d+/i', $chave);
        }

        $html = '<p>Body before table.</p>'
            .'<p>Table I Type locality Distribution Collection.</p>'
            .'<h2><strong>REFERENCES</strong></h2>';

        $enriquecido = (new EnriquecerHtmlLiteralDoPdf)->polir($html, $imagens);

        $this->assertMatchesRegularExpression('/tabela-pagina-\d+/', $enriquecido);
        $this->assertDoesNotMatchRegularExpression('/(?<!tabela-)pagina-\d+\.(?:jpe?g|png)/i', $enriquecido);
    }
}
