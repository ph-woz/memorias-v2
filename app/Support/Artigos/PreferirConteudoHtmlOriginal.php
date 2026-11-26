<?php

namespace App\Support\Artigos;

/**
 * Enriquece metadados a partir do HTML publicado (SciELO/Memórias),
 * sem substituir o corpo tipográfico — o PDF é a referência de conteúdo/formatação.
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
        $mesclado = $extracaoPdf;

        // Metadados editoriais (autor correspondente, instituições) podem vir do HTML
        // mesmo quando o corpo tipográfico vem do PDF.
        $this->mesclarCamposMetadado($mesclado, $extracaoHtml, $textoPdf);

        $resumoHtml = trim((string) ($extracaoHtml['resumo'] ?? ''));
        if ($resumoHtml !== '' && $this->trechoExisteNoPdf($resumoHtml, $textoPdf)) {
            $resumoPdf = trim((string) ($mesclado['resumo'] ?? ''));
            // Portal pode enriquecer o resumo com HTML; o corpo do artigo continua sendo o do PDF.
            if ($resumoPdf === '' || str_contains($resumoHtml, '<')) {
                $mesclado['resumo'] = $resumoHtml;
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

        // Corpo (conteudo_html) permanece o do PDF. Portal só informa origem dos metadados.
        if (trim((string) ($extracaoHtml['conteudo_html'] ?? '')) !== '') {
            $mesclado['fonte_metadados'] = (string) ($extracaoHtml['fonte'] ?? 'html_original');
            $mesclado['html_origem_url'] = $extracaoHtml['html_origem_url'] ?? null;
        }

        return $mesclado;
    }

    /**
     * @param  array<string, mixed>  $mesclado
     * @param  array<string, mixed>  $extracaoHtml
     */
    private function mesclarCamposMetadado(array &$mesclado, array $extracaoHtml, string $textoPdf): void
    {
        foreach (['endereco_instituicoes', 'autor_correspondente'] as $campo) {
            $htmlCampo = trim((string) ($extracaoHtml[$campo] ?? ''));

            if ($htmlCampo === '' || ! $this->trechoExisteNoPdf($htmlCampo, $textoPdf)) {
                continue;
            }

            $atual = trim((string) ($mesclado[$campo] ?? ''));

            if ($campo === 'autor_correspondente' && $atual !== '' && ! $this->blocoCorrespondenteMaisCompleto($htmlCampo, $atual)) {
                continue;
            }

            $mesclado[$campo] = $htmlCampo;
        }
    }

    private function blocoCorrespondenteMaisCompleto(string $candidato, string $atual): bool
    {
        $candidatoPlano = $this->normalizar($candidato);
        $atualPlano = $this->normalizar($atual);

        $candidatoCompleto = preg_match('/Financial support/iu', $candidatoPlano) === 1
            && preg_match('/Accepted\s+\d/iu', $candidatoPlano) === 1;
        $atualCompleto = preg_match('/Financial support/iu', $atualPlano) === 1
            && preg_match('/Accepted\s+\d/iu', $atualPlano) === 1;

        if ($candidatoCompleto && ! $atualCompleto) {
            return true;
        }

        return mb_strlen($candidatoPlano) > mb_strlen($atualPlano) + 20;
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

        if (
            preg_match('/[^\s<>]+@[^\s<>]+\.[^\s<>]+/u', $trecho, $email) === 1
            && mb_stripos($pdf, $email[0]) !== false
            && (
                preg_match('/Financial support/iu', $trecho) === 1
                || preg_match('/orcid\.org/iu', $trecho) === 1
                || preg_match('/Accepted\s+\d/iu', $trecho) === 1
            )
        ) {
            return true;
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
