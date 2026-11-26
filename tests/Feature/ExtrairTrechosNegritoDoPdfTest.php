<?php

namespace Tests\Feature;

use App\Support\Artigos\EnriquecerHtmlLiteralDoPdf;
use App\Support\Artigos\ExtrairTrechosNegritoDoPdf;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExtrairTrechosNegritoDoPdfTest extends TestCase
{
    public function test_extrai_subtitulos_negrito_das_fontes_do_pdf(): void
    {
        $fixture = base_path('tests/Fixtures/Artigos/triatomine-landscape-table.pdf');

        if (! is_file($fixture)) {
            $this->markTestSkipped('Fixture PDF ausente.');
        }

        Storage::fake('public');
        $caminho = 'artigos/importacoes/triatomine-negrito.pdf';
        Storage::disk('public')->put($caminho, (string) file_get_contents($fixture));

        $trechos = (new ExtrairTrechosNegritoDoPdf)->deCaminhoPublico($caminho);

        $this->assertNotEmpty($trechos);
        $this->assertTrue(
            collect($trechos)->contains(fn (string $t): bool => str_contains(mb_strtolower($t), 'triatominae until 1979')),
            'Esperava "Triatominae until 1979" detectado via fonte Bold do PDF. Got: '.implode(' | ', array_slice($trechos, 0, 10)),
        );

        $html = '<p>Introdução ao tema.</p><p>Triatominae until 1979</p><p>Corpo seguinte.</p>';
        $polido = (new EnriquecerHtmlLiteralDoPdf)->polir($html, [], $trechos);

        $this->assertStringContainsString('<strong>Triatominae until 1979</strong>', $polido);
    }
}
