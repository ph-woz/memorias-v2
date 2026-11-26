<?php

namespace App\Support\Artigos;

use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Config;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Extrai frases em negrito a partir das fontes tipográficas do PDF (Bold/Black/Heavy/Demi).
 */
class ExtrairTrechosNegritoDoPdf
{
    private readonly Parser $parser;

    public function __construct(?Parser $parser = null)
    {
        if ($parser !== null) {
            $this->parser = $parser;

            return;
        }

        $config = new Config;
        $config->setDataTmFontInfoHasToBeIncluded(true);
        $this->parser = new Parser([], $config);
    }

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
            $documento = $this->parser->parseFile($caminhoAbsoluto);
        } catch (Throwable) {
            return [];
        }

        $trechos = [];

        foreach ($documento->getPages() as $pagina) {
            $mapaFontes = [];

            try {
                foreach ($pagina->getFonts() as $id => $fonte) {
                    $mapaFontes[(string) $id] = (string) ($fonte->getDetails()['BaseFont'] ?? $id);
                }
            } catch (Throwable) {
                continue;
            }

            try {
                $linhas = $pagina->getDataTm();
            } catch (Throwable) {
                continue;
            }

            $atual = '';

            foreach ($linhas as $linha) {
                if (! is_array($linha)) {
                    continue;
                }

                $texto = (string) ($linha[1] ?? '');
                $fontId = (string) ($linha[2] ?? '');
                $base = $mapaFontes[$fontId]
                    ?? $mapaFontes[ltrim($fontId, '/')]
                    ?? $fontId;

                if ($texto === '') {
                    continue;
                }

                if ($this->fonteEhNegrito($base)) {
                    $atual .= $texto;

                    continue;
                }

                if ($atual !== '') {
                    $trechos[] = $this->normalizarTrecho($atual);
                    $atual = '';
                }
            }

            if ($atual !== '') {
                $trechos[] = $this->normalizarTrecho($atual);
            }
        }

        return $this->unicosSignificativos($trechos);
    }

    private function fonteEhNegrito(string $baseFont): bool
    {
        return preg_match('/(Bold|Black|Heavy|Demi)/i', $baseFont) === 1;
    }

    private function normalizarTrecho(string $texto): string
    {
        $texto = (new SanitizarTextoUtf8)->sanitizar($texto);
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }

    /**
     * @param  list<string>  $trechos
     * @return list<string>
     */
    private function unicosSignificativos(array $trechos): array
    {
        $vistos = [];
        $saida = [];

        foreach ($trechos as $trecho) {
            if (mb_strlen($trecho) < 6) {
                continue;
            }

            if (preg_match('/^[\d\s|.\-–—]+$/u', $trecho) === 1) {
                continue;
            }

            $chave = mb_strtolower($trecho);

            if (isset($vistos[$chave])) {
                continue;
            }

            $vistos[$chave] = true;
            $saida[] = $trecho;
        }

        usort($saida, fn (string $a, string $b): int => mb_strlen($b) <=> mb_strlen($a));

        return $saida;
    }
}
