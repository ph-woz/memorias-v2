<?php

namespace App\Support\Artigos;

/**
 * Normaliza instituições e bloco de autor correspondente do PDF:
 * remove lixo de afiliação (ex.: "1 / +") e restaura quebras de linha do layout editorial.
 */
class NormalizarMetadadosEditoriaisPdf
{
    public function enderecoInstituicoes(string $valor): string
    {
        $texto = $this->textoPlano($valor);

        if ($texto === '') {
            return '';
        }

        // Descarta byline/índices de autor ("1 / + , Carolina…") antes da 1ª instituição.
        if (preg_match(
            '/\b(\d{1,2}\s+(?:Fundação|Fundacao|Universidade|Institut[eo]?|Centro|College|University|Departamento|Department|Laborat)\b.*)$/isu',
            $texto,
            $m,
        ) === 1) {
            $texto = trim($m[1]);
        }

        // Corta abstract/corpo/sidebar colados após a última afiliação.
        $texto = preg_replace(
            '/\s+(?:Chagas disease|Abstract\b|Resumo\b|BACKGROUND\b|Key words?:|doi:|Financial support:|Despite the).*$/isu',
            '',
            $texto,
        ) ?? $texto;

        // Remove marcadores "1 / +" residuais (nunca o número da afiliação institucional).
        $texto = preg_replace('/\b\d{1,2}\s*\/\s*\+\b/u', '', $texto) ?? $texto;

        // "Brasil2 Fundação…" não tem \b entre letra e dígito — usa lookbehind \D.
        $partes = preg_split(
            '/(?<=\D)(?=\d{1,2}\s+(?:Fundação|Fundacao|Universidade|Institut[eo]?|Centro|College|University|Departamento|Department|Laborat)\b)/u',
            $texto,
        ) ?: [$texto];

        $linhas = [];

        foreach ($partes as $parte) {
            $parte = trim(preg_replace('/\s+/u', ' ', $parte) ?? $parte);

            if ($parte === '' || preg_match('/^\d{1,2}\s*$/u', $parte) === 1) {
                continue;
            }

            // Ignora restos de byline (só nomes próprios + índices).
            if (preg_match('/^(?:[A-ZÁÉÍÓÚ][a-záéíóú]+\s+){1,4}\d/u', $parte) === 1
                && preg_match('/\b(Fundação|Universidade|Institut)/iu', $parte) !== 1) {
                continue;
            }

            // Descarta pedaços que claramente não são afiliação.
            if (preg_match('/\b(Chagas disease|Abstract|Key words?|Financial support)\b/iu', $parte) === 1) {
                continue;
            }

            $linhas[] = e($parte);
        }

        // Uma linha por instituição com <br> — espaçamento do PDF, não margem de parágrafo.
        return $this->blocoEditorialCompacto($linhas !== [] ? $linhas : [e(trim($texto))]);
    }

    public function autorCorrespondente(string $valor): string
    {
        $texto = $this->textoPlano($valor);

        if ($texto === '') {
            return '';
        }

        // Corta prosa do corpo colada após Accepted.
        $texto = preg_replace('/\s+Despite the .*$/isu', '', $texto) ?? $texto;
        $texto = preg_replace('/\s+How to cite:.*/isu', '', $texto) ?? $texto;
        $texto = preg_replace('/\s+Handling editor:.*/isu', '', $texto) ?? $texto;

        // Restaura quebras do layout Memórias (Financial → Corresponding → ORCID → Received → Accepted).
        $texto = preg_replace(
            '/\s*\+?\s*(Corresponding authors?\s*:)/iu',
            "\n+ $1",
            $texto,
        ) ?? $texto;

        // Também quando ORCID/Received/Accepted colam sem espaço no e-mail.
        $texto = preg_replace('/(?<=\S)(?=https?:\/\/orcid\.org\/)/iu', "\n", $texto) ?? $texto;
        $texto = preg_replace('/\s+(https?:\/\/orcid\.org\/\S+)/iu', "\n$1", $texto) ?? $texto;
        $texto = preg_replace('/(?<=\S)(?=Received\s+\d{1,2}\s+\w+\s+\d{4})/iu', "\n", $texto) ?? $texto;
        $texto = preg_replace('/\s+(Received\s+\d{1,2}\s+\w+\s+\d{4})/iu', "\n$1", $texto) ?? $texto;
        $texto = preg_replace('/(?<=\S)(?=Accepted\s+\d{1,2}\s+\w+\s+\d{4})/iu', "\n", $texto) ?? $texto;
        $texto = preg_replace('/\s+(Accepted\s+\d{1,2}\s+\w+\s+\d{4})/iu', "\n$1", $texto) ?? $texto;

        // Segunda linha típica do funding (productivity grant), com ou sem espaço após o ponto.
        $texto = preg_replace(
            '/(FAPERJ\.)\s*(JC,|[A-Z]{2,}(?:,\s*[A-Z]{2,})*\s+and\s+[A-Z]{2,}\s+are\b)/u',
            "$1\n$2",
            $texto,
        ) ?? $texto;

        $brutas = preg_split('/\n+/u', $texto) ?: [$texto];
        $linhas = [];

        foreach ($brutas as $linha) {
            $linha = trim(preg_replace('/[ \t]+/u', ' ', $linha) ?? $linha);

            if ($linha === '') {
                continue;
            }

            $linhas[] = e($linha);
        }

        return $this->blocoEditorialCompacto($linhas !== [] ? $linhas : [e($texto)]);
    }

    /**
     * @param  list<string>  $linhasJaEscapadas
     */
    private function blocoEditorialCompacto(array $linhasJaEscapadas): string
    {
        return '<p>'.implode('<br>', $linhasJaEscapadas).'</p>';
    }

    private function textoPlano(string $htmlOuTexto): string
    {
        $texto = html_entity_decode(strip_tags($htmlOuTexto), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = str_replace(["\r\n", "\r"], "\n", $texto);
        $texto = preg_replace('/[ \t]+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }
}
