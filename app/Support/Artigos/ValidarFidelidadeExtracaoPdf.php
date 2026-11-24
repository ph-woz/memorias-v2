<?php

namespace App\Support\Artigos;

use RuntimeException;

class ValidarFidelidadeExtracaoPdf
{
    /**
     * @param  array<string, mixed>  $extracao
     */
    public function validar(string $textoPdf, array $extracao): void
    {
        $fonte = $this->normalizarParaBusca($textoPdf);

        if ($fonte === '') {
            throw new RuntimeException('Não foi possível validar fidelidade: texto do PDF vazio.');
        }

        $obrigatorios = ['titulo'];

        foreach ($obrigatorios as $campo) {
            $valor = trim((string) ($extracao[$campo] ?? ''));

            if ($valor === '') {
                throw new RuntimeException("Campo obrigatório ausente na extração: {$campo}.");
            }

            $this->afirmarPresente($fonte, $valor, $campo);
        }

        foreach (['resumo', 'endereco_instituicoes', 'autor_correspondente', 'marcador_inicio_corpo', 'marcador_inicio_referencias'] as $campo) {
            $valor = trim((string) ($extracao[$campo] ?? ''));

            if ($valor === '') {
                continue;
            }

            $this->afirmarPresente($fonte, $valor, $campo);
        }

        $doi = trim((string) ($extracao['doi'] ?? ''));
        if ($doi !== '') {
            $doiNormalizado = preg_replace('/\s+/u', '', mb_strtolower($doi)) ?? mb_strtolower($doi);
            $fonteSemEspacos = preg_replace('/\s+/u', '', $fonte) ?? $fonte;

            if ($doiNormalizado === '' || ! str_contains($fonteSemEspacos, $doiNormalizado)) {
                throw new RuntimeException(
                    'Fidelidade violada: o campo doi não aparece literalmente no PDF. A IA não pode reescrever o texto do autor.'
                );
            }
        }

        foreach (['autores', 'palavras_chave', 'areas_tematicas'] as $lista) {
            $itens = $extracao[$lista] ?? [];

            if (! is_array($itens)) {
                continue;
            }

            foreach ($itens as $indice => $item) {
                $valor = trim((string) $item);

                if ($valor === '') {
                    continue;
                }

                $this->afirmarPresente($fonte, $valor, "{$lista}[{$indice}]");
            }
        }
    }

    private function afirmarPresente(string $fonteNormalizada, string $trecho, string $campo): void
    {
        $needle = $this->normalizarParaBusca($trecho);

        if ($needle === '') {
            return;
        }

        if (! str_contains($fonteNormalizada, $needle)) {
            throw new RuntimeException(
                "Fidelidade violada: o campo {$campo} não aparece literalmente no PDF. A IA não pode reescrever o texto do autor."
            );
        }
    }

    public function normalizarParaBusca(string $texto): string
    {
        return $this->normalizarComMapa($texto)['texto'];
    }

    /**
     * Localiza no texto original o trecho cuja forma normalizada corresponde ao needle.
     * Retorna o substring literal do PDF (com quebras originais) ou null.
     */
    public function localizarTrechoLiteral(string $haystack, string $needle): ?string
    {
        $needle = trim($needle);

        if ($needle === '') {
            return null;
        }

        $posExata = mb_stripos($haystack, $needle);

        if ($posExata !== false) {
            return mb_substr($haystack, $posExata, mb_strlen($needle));
        }

        $mapaHay = $this->normalizarComMapa($haystack);
        $needleNorm = $this->normalizarParaBusca($needle);

        if ($needleNorm === '' || $mapaHay['texto'] === '') {
            return null;
        }

        $posNorm = mb_strpos($mapaHay['texto'], $needleNorm);

        if ($posNorm === false) {
            return null;
        }

        $inicioOrig = $mapaHay['mapa'][$posNorm] ?? null;
        $fimNorm = $posNorm + mb_strlen($needleNorm) - 1;
        $fimOrig = $mapaHay['mapa'][$fimNorm] ?? null;

        if ($inicioOrig === null || $fimOrig === null || $fimOrig < $inicioOrig) {
            return null;
        }

        return mb_substr($haystack, $inicioOrig, $fimOrig - $inicioOrig + 1);
    }

    /**
     * @return array{texto: string, mapa: list<int>}
     */
    public function normalizarComMapa(string $texto): array
    {
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = strip_tags($texto);
        $texto = str_replace(["\u{00AD}", "\u{200B}", "\u{FEFF}"], '', $texto);

        $chars = preg_split('//u', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $out = '';
        $mapa = [];
        $pendenteEspaco = false;
        $indiceEspaco = null;

        foreach ($chars as $i => $char) {
            $lower = mb_strtolower($char);
            $lower = preg_replace('/[\x{2010}-\x{2015}\x{2212}]/u', '-', $lower) ?? $lower;

            if (preg_match('/\s/u', $lower) === 1) {
                if ($out !== '') {
                    $pendenteEspaco = true;
                    $indiceEspaco = $i;
                }

                continue;
            }

            if ($pendenteEspaco) {
                $out .= ' ';
                $mapa[] = $indiceEspaco ?? $i;
                $pendenteEspaco = false;
                $indiceEspaco = null;
            }

            $out .= $lower;
            $mapa[] = $i;
        }

        return [
            'texto' => $out,
            'mapa' => $mapa,
        ];
    }
}
