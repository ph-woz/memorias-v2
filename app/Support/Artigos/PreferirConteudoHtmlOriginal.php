<?php

namespace App\Support\Artigos;

/**
 * Quando existe HTML editorial publicado (SciELO/Memórias), prefere esse conteúdo
 * ao texto reconstruído do PDF — mantendo fidelidade verificada contra o PDF.
 */
class PreferirConteudoHtmlOriginal
{
    /**
     * @param  array<string, mixed>  $extracaoPdf
     * @param  array<string, mixed>  $extracaoHtml
     * @return array<string, mixed>
     */
    public function preferir(array $extracaoPdf, array $extracaoHtml, string $textoPdf): array
    {
        $htmlCorpo = trim((string) ($extracaoHtml['conteudo_html'] ?? ''));
        $pdfCorpo = trim((string) ($extracaoPdf['conteudo_html'] ?? ''));

        if ($htmlCorpo === '' || ! $this->corpoHtmlCompativelComPdf($htmlCorpo, $textoPdf, $pdfCorpo)) {
            return $extracaoPdf;
        }

        $mesclado = $extracaoPdf;
        $mesclado['conteudo_html'] = $htmlCorpo;
        $mesclado['fonte_conteudo'] = (string) ($extracaoHtml['fonte'] ?? 'html_original');
        $mesclado['html_origem_url'] = $extracaoHtml['html_origem_url'] ?? null;

        $resumoHtml = trim((string) ($extracaoHtml['resumo'] ?? ''));
        if ($resumoHtml !== '' && $this->trechoExisteNoPdf($resumoHtml, $textoPdf)) {
            $mesclado['resumo'] = $resumoHtml;
        }

        foreach (['endereco_instituicoes', 'autor_correspondente'] as $campo) {
            $htmlCampo = trim((string) ($extracaoHtml[$campo] ?? ''));

            if ($htmlCampo !== '' && $this->trechoExisteNoPdf($htmlCampo, $textoPdf)) {
                $mesclado[$campo] = $htmlCampo;
            }
        }

        $refs = $extracaoHtml['referencias'] ?? null;
        if (is_array($refs) && count($refs) >= 3) {
            $mesclado['referencias'] = $refs;
        }

        $keywords = $extracaoHtml['palavras_chave'] ?? null;
        if (is_array($keywords) && $keywords !== [] && empty($mesclado['palavras_chave'])) {
            $mesclado['palavras_chave'] = $keywords;
        }

        return $mesclado;
    }

    private function corpoHtmlCompativelComPdf(string $htmlCorpo, string $textoPdf, string $pdfCorpoHtml): bool
    {
        $htmlTexto = $this->normalizar($htmlCorpo);
        $pdfTexto = $this->normalizar($textoPdf);
        $pdfCorpo = $this->normalizar($pdfCorpoHtml);

        if (mb_strlen($htmlTexto) < 400) {
            return false;
        }

        $inicioHtml = mb_substr($htmlTexto, 0, 64);
        $inicioNoPdf = $inicioHtml !== '' && mb_stripos($pdfTexto, $inicioHtml) !== false;
        $inicioNoCorpoPdf = $inicioHtml !== '' && mb_stripos($pdfCorpo, mb_substr($inicioHtml, 0, 40)) !== false;

        if (! $inicioNoPdf && ! $inicioNoCorpoPdf) {
            return false;
        }

        $tagsHtml = preg_match_all('/<(em|i|sup|strong)\b/i', $htmlCorpo);
        $tagsPdf = preg_match_all('/<(em|i|sup|strong)\b/i', $pdfCorpoHtml);
        $tamanhoOk = mb_strlen($htmlTexto) >= (int) floor(max(400, mb_strlen($pdfCorpo) * 0.55));
        $tipografiaOk = $tagsHtml >= 15 && $tagsHtml >= (int) floor($tagsPdf * 0.5);

        // Prefere o HTML publicado quando cobre bem o texto e já traz tipografia editorial.
        return $tamanhoOk || $tipografiaOk;
    }

    private function trechoExisteNoPdf(string $trecho, string $textoPdf): bool
    {
        $trecho = $this->normalizar($trecho);
        $pdf = $this->normalizar($textoPdf);

        if (mb_strlen($trecho) < 24) {
            return false;
        }

        $candidatos = array_values(array_unique(array_filter([
            $trecho,
            ltrim($trecho, "+ \t"),
            preg_replace('/^\d+\s*/u', '', $trecho) ?? $trecho,
            preg_replace('/^\+\s*/u', '', $trecho) ?? $trecho,
        ])));

        foreach ($candidatos as $candidato) {
            if (mb_strlen($candidato) < 24) {
                continue;
            }

            $amostra = mb_substr($candidato, 0, min(80, mb_strlen($candidato)));

            if ($amostra !== '' && mb_stripos($pdf, $amostra) !== false) {
                return true;
            }
        }

        if (mb_strlen($trecho) >= 40) {
            $meio = mb_substr($trecho, 8, 48);

            if ($meio !== '' && mb_stripos($pdf, $meio) !== false) {
                return true;
            }
        }

        return false;
    }

    private function normalizar(string $htmlOuTexto): string
    {
        $texto = html_entity_decode(strip_tags($htmlOuTexto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }
}
