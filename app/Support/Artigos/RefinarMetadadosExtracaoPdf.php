<?php

namespace App\Support\Artigos;

/**
 * Corrige metadados da IA quando o PDF mistura cabeçalho editorial com título/resumo
 * e completa lacunas com heurísticas do layout Memórias.
 */
class RefinarMetadadosExtracaoPdf
{
    public function __construct(
        private readonly HeuristicaMetadadosPdfMemorias $heuristica = new HeuristicaMetadadosPdfMemorias,
    ) {}

    /**
     * @param  array<string, mixed>  $metadados
     * @return array<string, mixed>
     */
    public function refinar(string $textoPdf, array $metadados): array
    {
        $tituloHeuristico = $this->extrairTituloDoLayout($textoPdf);
        $tituloIa = trim((string) ($metadados['titulo'] ?? ''));

        if ($tituloHeuristico !== null && ($tituloIa === '' || $this->tituloPareceCabecalho($tituloIa))) {
            $metadados['titulo'] = $tituloHeuristico;
        } elseif ($tituloIa !== '' && $this->tituloPareceCabecalho($tituloIa)) {
            $limpo = $this->limparCabecalhoDoTitulo($tituloIa);

            if ($limpo !== '') {
                $metadados['titulo'] = $limpo;
            }
        }

        $resumo = trim((string) ($metadados['resumo'] ?? ''));

        if ($resumo !== '') {
            $metadados['resumo'] = $this->limparResumo($resumo);
        }

        if (trim((string) ($metadados['resumo'] ?? '')) === '') {
            $resumoHeuristico = $this->extrairResumoDoLayout($textoPdf);

            if ($resumoHeuristico !== null) {
                $metadados['resumo'] = $resumoHeuristico;
            }
        }

        $doi = trim((string) ($metadados['doi'] ?? ''));

        if ($doi !== '') {
            $metadados['doi'] = preg_replace('/\s+/u', '', $doi) ?? $doi;
        }

        return $this->heuristica->completar($textoPdf, $metadados);
    }

    private function tituloPareceCabecalho(string $titulo): bool
    {
        $t = mb_strtolower($titulo);

        return str_contains($t, 'memorias.ioc')
            || str_contains($t, 'mem inst oswaldo')
            || str_contains($t, 'vol.')
            || str_contains($t, '|10')
            || preg_match('/\b(janeiro|rio de janeiro),\s*vol\./iu', $titulo) === 1
            || preg_match('/\breview\b/iu', $titulo) === 1 && mb_strlen($titulo) > 120;
    }

    private function limparCabecalhoDoTitulo(string $titulo): string
    {
        $titulo = preg_replace('/^.*?\breview\b\s*/iu', '', $titulo) ?? $titulo;
        $titulo = preg_replace('/^.*?(online\s*\|\s*\S+\s*)/iu', '', $titulo) ?? $titulo;
        $titulo = preg_replace('/\s+/u', ' ', $titulo) ?? $titulo;

        return trim($titulo);
    }

    private function extrairTituloDoLayout(string $textoPdf): ?string
    {
        $inicio = mb_substr($textoPdf, 0, 3500);

        if (preg_match(
            '/\b(?:REVIEW|ORIGINAL ARTICLE|ARTICLE|RESEARCH ARTICLE|EDITORIAL)\b\s+(.+?)(?=\n\s*[A-Z][a-z]+ [A-ZÁÉÍÓÚÂÊÔÃÕÇ][^\n]{0,80}\n)/su',
            $inicio,
            $m,
        ) === 1) {
            $titulo = $this->compactarLinhas($m[1]);

            if (mb_strlen($titulo) >= 20 && ! $this->tituloPareceCabecalho($titulo)) {
                return $titulo;
            }
        }

        // Fallback: linha(s) após cabeçalho Mem Inst..., antes do primeiro autor.
        if (preg_match(
            '/memorias\.ioc[^\n]*\n(?:REVIEW|ORIGINAL ARTICLE|ARTICLE)?\s*(.+?)(?=\n\s*[A-Z][a-z]+ [A-ZÁÉÍÓÚÂÊÔÃÕÇ][a-záéíóúâêôãõç]+)/su',
            $inicio,
            $m,
        ) === 1) {
            $titulo = $this->compactarLinhas($m[1]);
            $titulo = preg_replace('/^(REVIEW|ORIGINAL ARTICLE|ARTICLE)\b\s*/iu', '', $titulo) ?? $titulo;

            if (mb_strlen($titulo) >= 20) {
                return trim($titulo);
            }
        }

        return null;
    }

    private function limparResumo(string $resumo): string
    {
        $linhas = preg_split("/\n+/u", $resumo) ?: [$resumo];
        $uteis = [];

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            if ($linha === '') {
                continue;
            }

            if ($this->linhaPareceAfiliacaoOuMarcador($linha)) {
                continue;
            }

            $uteis[] = $linha;
        }

        $texto = trim(implode(' ', $uteis));
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return $texto;
    }

    private function extrairResumoDoLayout(string $textoPdf): ?string
    {
        $inicio = mb_substr($textoPdf, 0, 8000);

        // Após bloco de afiliações (país/instituição), começa o abstract em prosa.
        if (preg_match(
            '/(?:Brasil|Brazil|France|USA|Argentina|Chile|Peru|Mexico|México|Portugal|Spain|España)\s*\n+([A-Z][^.]{40,}(?:\.[^\n]*){2,})/su',
            $inicio,
            $m,
        ) === 1) {
            $candidato = $this->compactarLinhas($m[1]);

            if (mb_strlen($candidato) >= 120) {
                return mb_substr($candidato, 0, 4000);
            }
        }

        return null;
    }

    private function linhaPareceAfiliacaoOuMarcador(string $linha): bool
    {
        if (preg_match('/^\d{1,2}$/u', $linha) === 1) {
            return true;
        }

        if (preg_match('/^(abstract|resumo|summary)\b[:\s]*/iu', $linha) === 1) {
            return true;
        }

        return preg_match(
            '/\b(institut|universit|fundação|fundacao|laborat|faculdade|departamento|college|university|centre|center)\b/iu',
            $linha,
        ) === 1
            && mb_strlen($linha) < 220;
    }

    private function compactarLinhas(string $texto): string
    {
        $texto = preg_replace("/[ \t]*\n[ \t]*/u", ' ', $texto) ?? $texto;
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }
}
