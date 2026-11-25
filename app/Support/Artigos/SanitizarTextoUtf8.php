<?php

namespace App\Support\Artigos;

/**
 * PDFs das Memórias frequentemente trazem bytes inválidos em UTF-8 (ícones ORCID,
 * fontes embutidas). Regex com flag /u e mb_* falham e “somem” o texto.
 */
class SanitizarTextoUtf8
{
    public function __invoke(string $texto): string
    {
        return $this->sanitizar($texto);
    }

    public function sanitizar(string $texto): string
    {
        $texto = str_replace("\0", '', $texto);

        if ($texto === '') {
            return '';
        }

        if (! mb_check_encoding($texto, 'UTF-8')) {
            if (function_exists('mb_scrub')) {
                $texto = mb_scrub($texto, 'UTF-8');
            } else {
                $texto = mb_convert_encoding($texto, 'UTF-8', 'UTF-8');
            }
        }

        // Private Use / ícones de ORCID do PDF → espaço.
        $texto = preg_replace('/[\x{E000}-\x{F8FF}]/u', ' ', $texto) ?? $texto;
        $texto = preg_replace('/\x{FFFD}/u', ' ', $texto) ?? $texto;

        return $texto;
    }
}
