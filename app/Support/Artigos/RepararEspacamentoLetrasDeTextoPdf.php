<?php

namespace App\Support\Artigos;

/**
 * Corrige espaços artificiais entre letras gerados por extração de PDF
 * landscape/rotacionado (ex.: "Jacu nd á" → "Jacundá", "Tole d o" → "Toledo").
 *
 * Não altera frases reais com partículas ("Rio de Janeiro", "Near a cave").
 */
class RepararEspacamentoLetrasDeTextoPdf
{
    /** @var list<string> */
    private const PARTICULAS_FRASE = [
        'de', 'del', 'da', 'do', 'das', 'dos', 'of', 'the', 'and', 'or',
        'la', 'las', 'el', 'en', 'in', 'near', 'under', 'with',
        'from', 'for', 'at', 'on', 'to', 'by', 'as', 'vs',
        'near', 'para', 'por',
    ];

    public function reparar(string $texto): string
    {
        if ($texto === '') {
            return '';
        }

        $linhas = preg_split('/\R/u', $texto) ?: [$texto];
        $saida = [];

        foreach ($linhas as $linha) {
            $saida[] = $this->repararLinha($linha);
        }

        return implode("\n", $saida);
    }

    public function repararHtmlTabela(string $html): string
    {
        return $this->repararHtml($html);
    }

    /**
     * Repara espaços artificiais em qualquer texto do HTML (tabela e prosa).
     */
    public function repararHtml(string $html): string
    {
        if ($html === '') {
            return '';
        }

        if (! str_contains($html, '<')) {
            return $this->reparar($html);
        }

        return (string) preg_replace_callback(
            '/>([^<]+)</u',
            function (array $m): string {
                return '>'.$this->repararLinha($m[1]).'<';
            },
            $html,
        );
    }

    public function pareceEspacamentoArtificial(string $texto): bool
    {
        $plano = trim(html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $plano = trim(preg_replace('/\s+/u', ' ', $plano) ?? $plano);

        if ($plano === '') {
            return false;
        }

        if (preg_match('/\(\d(?:\s+\d)+\)/u', $plano) === 1) {
            return true;
        }

        if (preg_match('/^(.+?)(\s*\(\d+\))?$/u', $plano, $m) !== 1) {
            return false;
        }

        return $this->nomeTemEspacamentoArtificial(trim($m[1]));
    }

    private function repararLinha(string $linha): string
    {
        $linha = html_entity_decode($linha, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $linha = trim(preg_replace('/[ \t]+/u', ' ', $linha) ?? $linha);

        // (2 0) / (3 1) → (20) / (31)
        $linha = preg_replace_callback(
            '/\(([\d\s]+)\)/u',
            function (array $m): string {
                $digitos = preg_replace('/\s+/u', '', $m[1]) ?? $m[1];

                return '('.$digitos.')';
            },
            $linha,
        ) ?? $linha;

        // Localidade tipica da TABLE I: "Jacu nd á (1)" / "Tole d o (3)"
        if (preg_match('/^(.+?)(\s*\(\d+\))$/u', $linha, $m) === 1) {
            $nome = trim($m[1]);
            $sufixo = $m[2];

            if ($this->nomeTemEspacamentoArtificial($nome)) {
                return $this->juntarLetras($nome).$sufixo;
            }

            return $linha;
        }

        if ($this->nomeTemEspacamentoArtificial($linha)) {
            return $this->juntarLetras($linha);
        }

        // Trechos internos fragmentados (mantém o restante da linha).
        return preg_replace_callback(
            '/(?<!\p{L})(\p{L}+(?:\s+\p{L}+){1,8})(?!\p{L})/u',
            function (array $m): string {
                $bloco = $m[1];

                return $this->nomeTemEspacamentoArtificial($bloco)
                    ? $this->juntarLetras($bloco)
                    : $bloco;
            },
            $linha,
        ) ?? $linha;
    }

    private function juntarLetras(string $nome): string
    {
        return preg_replace('/(?<=\p{L})\s+(?=\p{L})/u', '', $nome) ?? $nome;
    }

    private function nomeTemEspacamentoArtificial(string $nome): bool
    {
        $nome = trim(preg_replace('/\s+/u', ' ', $nome) ?? $nome);

        if ($nome === '' || ! str_contains($nome, ' ')) {
            return false;
        }

        if (str_contains($nome, ',')) {
            return false;
        }

        $tokens = preg_split('/\s+/u', $nome) ?: [];

        if (count($tokens) < 2) {
            return false;
        }

        $alfabeticos = [];

        foreach ($tokens as $token) {
            if (preg_match('/^\p{L}+$/u', $token) !== 1) {
                return false;
            }

            $alfabeticos[] = $token;
        }

        foreach ($alfabeticos as $token) {
            if (in_array(mb_strtolower($token), self::PARTICULAS_FRASE, true)) {
                return false;
            }
        }

        // "Near a cave": near já bloqueou. "a cave" sozinho não chega aqui com near.
        // Artigo "a"/"o" entre duas palavras longas (>=4) é frase real.
        if (count($alfabeticos) === 3) {
            $meio = mb_strtolower($alfabeticos[1]);
            if (in_array($meio, ['a', 'o', 'e', 'y'], true)
                && mb_strlen($alfabeticos[0]) >= 4
                && mb_strlen($alfabeticos[2]) >= 4) {
                return false;
            }
        }

        $singles = 0;
        $curtos = 0;
        $comprimento = 0;

        foreach ($alfabeticos as $token) {
            $len = mb_strlen($token);
            $comprimento += $len;

            if ($len === 1) {
                $singles++;
            }

            if ($len <= 2) {
                $curtos++;
            }
        }

        $media = $comprimento / count($alfabeticos);

        // Fragmentos de OCR: "Tole d o", "Jacu nd á", "Col i m a", "A r ica"
        return $singles >= 1
            || ($curtos >= 2 && count($alfabeticos) >= 3)
            || ($media <= 2.6 && count($alfabeticos) >= 3);
    }
}
