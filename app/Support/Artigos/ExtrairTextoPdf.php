<?php

namespace App\Support\Artigos;

use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Throwable;

class ExtrairTextoPdf
{
    public function __construct(
        private readonly Parser $parser = new Parser,
    ) {}

    public function deCaminhoPublico(string $caminho): string
    {
        $absoluto = Storage::disk('public')->path($caminho);

        return $this->deArquivo($absoluto);
    }

    public function deArquivo(string $caminhoAbsoluto): string
    {
        try {
            $texto = $this->parser->parseFile($caminhoAbsoluto)->getText();
        } catch (Throwable) {
            return '';
        }

        $texto = (new SanitizarTextoUtf8)->sanitizar($texto);
        $texto = preg_replace("/[ \t]+/u", ' ', $texto) ?? $texto;
        // Une hifenização tipográfica de fim de linha do PDF: "Cha-\ngas" → "Chagas".
        $texto = preg_replace('/(\p{L})-\R(\p{Ll})/u', '$1$2', $texto) ?? $texto;

        return trim($texto);
    }
}
