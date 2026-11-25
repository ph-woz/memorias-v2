<?php

namespace App\Support\Artigos;

/**
 * Remove a numeração inicial da referência: a ordem no CMS/`<ol>` define o número.
 * Evita "71. 71. Autor…" quando o texto ainda traz "71." / "71 " / "[71]".
 */
class NormalizarTextoReferencia
{
    public function __invoke(string $texto): string
    {
        return $this->normalizar($texto);
    }

    public function normalizar(string $texto): string
    {
        $texto = trim(html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;
        $texto = trim($texto);

        // "01." / "1." / "1)" / "[1]" / "1 " no início.
        $texto = preg_replace('/^(?:\[\d{1,3}\]|\d{1,3}[.)]?)\s+/u', '', $texto) ?? $texto;

        return trim($texto);
    }
}
