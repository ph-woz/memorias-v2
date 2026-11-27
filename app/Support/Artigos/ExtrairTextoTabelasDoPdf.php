<?php

namespace App\Support\Artigos;

use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Extrai o texto das páginas do PDF que contêm tabelas científicas
 * landscape/rotacionadas (ex.: TABLE I), sem rasterizar.
 */
class ExtrairTextoTabelasDoPdf
{
    public function __construct(
        private readonly Parser $parser = new Parser,
        private readonly RepararEspacamentoLetrasDeTextoPdf $repararEspacamento = new RepararEspacamentoLetrasDeTextoPdf,
    ) {}

    /**
     * @return list<string>
     */
    public function deCaminhoPublico(string $caminhoPdfPublico): array
    {
        if (! Storage::disk('public')->exists($caminhoPdfPublico)) {
            return [];
        }

        return $this->deArquivo(Storage::disk('public')->path($caminhoPdfPublico));
    }

    /**
     * @return list<string>
     */
    public function deArquivo(string $caminhoAbsoluto): array
    {
        try {
            $paginas = $this->parser->parseFile($caminhoAbsoluto)->getPages();
        } catch (Throwable) {
            return [];
        }

        $textos = [];

        foreach ($paginas as $pagina) {
            try {
                $texto = (string) $pagina->getText();
            } catch (Throwable) {
                continue;
            }

            if ($this->paginaPareceTabelaLandscape($texto)) {
                $textos[] = $this->repararEspacamento->reparar($texto);
            }
        }

        return $textos;
    }

    private function paginaPareceTabelaLandscape(string $texto): bool
    {
        $normalizado = mb_strtolower(preg_replace('/\s+/u', ' ', $texto) ?? $texto);

        if (trim($normalizado) === '') {
            return false;
        }

        $cabecalhos = 0;

        foreach (['type locality', 'distribution', 'collection', 'ecotope', 'dna sequence'] as $cabecalho) {
            if (str_contains($normalizado, $cabecalho)) {
                $cabecalhos++;
            }
        }

        if ($cabecalhos >= 3) {
            return true;
        }

        $temTituloTabela = preg_match('/\btable\s+(?:[ivxlcdm]+|\d+)\b/iu', $texto) === 1
            || preg_match('/\btabela\s+(?:[ivxlcdm]+|\d+)\b/iu', $texto) === 1;

        return $temTituloTabela && $cabecalhos >= 2;
    }
}
