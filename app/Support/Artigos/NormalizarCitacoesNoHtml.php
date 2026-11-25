<?php

namespace App\Support\Artigos;

/**
 * Normaliza citações em <sup> para o formato editorial <sup>(n)</sup> / <sup>(n, m)</sup>.
 * Ignora anos, volumes e páginas que por engano entrem no markup da citação.
 */
class NormalizarCitacoesNoHtml
{
    public function normalizar(string $html): string
    {
        $html = trim($html);

        if ($html === '' || ! str_contains(mb_strtolower($html), '<sup')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<sup\b[^>]*>(.*?)<\/sup>(?:\s*<\/sup>)?/is',
            function (array $m): string {
                $numeros = $this->extrairNumerosCitacao($m[1]);

                if ($numeros === []) {
                    return $m[0];
                }

                return '<sup>('.implode(', ', $numeros).')</sup>';
            },
            $html,
        );
    }

    /**
     * @return list<string>
     */
    public function extrairNumerosCitacao(string $internoHtml): array
    {
        // 1) Âncoras do portal Memórias / SciELO: só o texto do link.
        if (preg_match_all(
            '/<a\b[^>]*class="[^"]*(?:click-reference|xref)[^"]*"[^>]*>\s*(\d{1,3})\s*<\/a>/i',
            $internoHtml,
            $links,
        ) > 0) {
            return $this->unicos($links[1]);
        }

        if (preg_match_all('/<a\b[^>]*>\s*(\d{1,3})\s*<\/a>/i', $internoHtml, $links) > 0) {
            // Só aceita se o conteúdo textual limpo for só a lista de citações
            // (evita <a> com título bibliográfico longo).
            $textoDosLinks = trim(html_entity_decode(strip_tags($internoHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $textoDosLinks = trim(preg_replace('/\s+/u', ' ', $textoDosLinks) ?? $textoDosLinks, " \t()[]");

            if (preg_match('/^\d{1,3}(?:\s*,\s*\d{1,3})*$/u', $textoDosLinks) === 1) {
                return $this->unicos($links[1]);
            }
        }

        $texto = trim(html_entity_decode(strip_tags($internoHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);
        $texto = trim($texto, " \t()[]");

        if ($texto === '' || preg_match('/\d/u', $texto) !== 1) {
            return [];
        }

        // 2) Lista pura: "77, 78" / "77,78" / "30, 31".
        if (preg_match('/^\d{1,3}(?:\s*,\s*\d{1,3})*$/u', $texto) === 1) {
            return $this->unicos(preg_split('/\s*,\s*/u', $texto) ?: []);
        }

        // 2b) Lista contaminada com anos/páginas: "77, 1977, 2009, 9, 1, 123, 8, 78, 2017…".
        if (preg_match('/^\d+(?:\s*,\s*\d+)*$/u', $texto) === 1) {
            $recuperados = $this->recuperarCitacoesDeListaContaminada($texto);

            if ($recuperados !== []) {
                return $recuperados;
            }
        }

        // 3) Prefixo "77, 78. Autor…" (bibliografia colada dentro do <sup>).
        if (preg_match('/^(\d{1,3}(?:\s*,\s*\d{1,3})*)\s*\.\s+\p{L}/u', $texto, $m) === 1) {
            return $this->unicos(preg_split('/\s*,\s*/u', $m[1]) ?: []);
        }

        // 4) Entradas "77. Autor… 78. Autor…" coladas: pega só os números de entrada.
        if (preg_match_all('/(?:^|;\s*|,\s*)(\d{1,3})\.\s+\p{L}/u', $texto, $m) > 0) {
            return $this->unicos($m[1]);
        }

        return [];
    }

    /**
     * @return list<string>
     */
    private function recuperarCitacoesDeListaContaminada(string $texto): array
    {
        $tokens = preg_split('/\s*,\s*/u', $texto) ?: [];

        if ($tokens === []) {
            return [];
        }

        $temAno = false;
        foreach ($tokens as $token) {
            if ($this->pareceAno($token)) {
                $temAno = true;
                break;
            }
        }

        if (! $temAno) {
            return [];
        }

        $citacoes = [];

        // Números de citação antes do primeiro ano.
        foreach ($tokens as $token) {
            if ($this->pareceAno($token)) {
                break;
            }

            if (preg_match('/^\d{1,3}$/u', $token) === 1 && (int) $token >= 1) {
                $citacoes[] = $token;

                continue;
            }

            break;
        }

        // Números imediatamente seguidos de um ano (= início de entrada bibliográfica).
        for ($i = 0, $total = count($tokens) - 1; $i < $total; $i++) {
            if (
                preg_match('/^\d{1,3}$/u', $tokens[$i]) === 1
                && (int) $tokens[$i] >= 1
                && $this->pareceAno($tokens[$i + 1])
            ) {
                $citacoes[] = $tokens[$i];
            }
        }

        return $this->unicos($citacoes);
    }

    private function pareceAno(string $token): bool
    {
        return preg_match('/^(17|18|19|20)\d{2}$/u', $token) === 1;
    }

    /**
     * @param  list<string>  $numeros
     * @return list<string>
     */
    private function unicos(array $numeros): array
    {
        $saida = [];

        foreach ($numeros as $numero) {
            $numero = trim((string) $numero);

            if ($numero === '' || preg_match('/^\d{1,3}$/u', $numero) !== 1) {
                continue;
            }

            if ((int) $numero < 1) {
                continue;
            }

            if (! in_array($numero, $saida, true)) {
                $saida[] = $numero;
            }
        }

        return $saida;
    }
}
