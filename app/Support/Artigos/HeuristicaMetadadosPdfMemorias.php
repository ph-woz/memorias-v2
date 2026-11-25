<?php

namespace App\Support\Artigos;

/**
 * Extrai metadados literais do layout típico dos PDFs das Memórias do IOC
 * (cabeçalho editorial → REVIEW → título → autores → afiliações → abstract → Key words → doi → Accepted → corpo).
 */
class HeuristicaMetadadosPdfMemorias
{
    /**
     * Preenche apenas campos vazios/fracos, sem sobrescrever valores literais já válidos.
     *
     * @param  array<string, mixed>  $metadados
     * @return array<string, mixed>
     */
    public function completar(string $textoPdf, array $metadados): array
    {
        $inicio = mb_substr($textoPdf, 0, 9000);

        if ($this->campoVazio($metadados['titulo'] ?? null)) {
            $titulo = $this->extrairTitulo($inicio);

            if ($titulo !== null) {
                $metadados['titulo'] = $titulo;
            }
        }

        if ($this->listaVazia($metadados['autores'] ?? null)) {
            $autores = $this->extrairAutores($inicio);

            if ($autores !== []) {
                $metadados['autores'] = $autores;
            }
        }

        if ($this->campoVazio($metadados['endereco_instituicoes'] ?? null)) {
            $instituicoes = $this->extrairInstituicoes($inicio);

            if ($instituicoes !== null) {
                $metadados['endereco_instituicoes'] = $instituicoes;
            }
        }

        if ($this->campoVazio($metadados['resumo'] ?? null)) {
            $resumo = $this->extrairResumo($inicio);

            if ($resumo !== null) {
                $metadados['resumo'] = $resumo;
            }
        }

        if ($this->listaVazia($metadados['palavras_chave'] ?? null)) {
            $keywords = $this->extrairPalavrasChave($inicio);

            if ($keywords !== []) {
                $metadados['palavras_chave'] = $keywords;
            }
        }

        if ($this->campoVazio($metadados['doi'] ?? null) || $this->doiPareceQuebrado((string) ($metadados['doi'] ?? ''))) {
            $doi = $this->extrairDoi($textoPdf);

            if ($doi !== null) {
                $metadados['doi'] = $doi;
            }
        }

        if ($this->campoVazio($metadados['autor_correspondente'] ?? null)) {
            $correspondente = $this->extrairAutorCorrespondente($textoPdf);

            if ($correspondente !== null) {
                $metadados['autor_correspondente'] = $correspondente;
            }
        }

        if ($this->campoVazio($metadados['marcador_inicio_corpo'] ?? null)) {
            $marcador = $this->extrairMarcadorCorpo($textoPdf);

            if ($marcador !== null) {
                $metadados['marcador_inicio_corpo'] = $marcador;
            }
        }

        if ($this->campoVazio($metadados['marcador_inicio_referencias'] ?? null)) {
            if (preg_match('/(?:^|\n)\s*(REFERENCES|REFERÊNCIAS|REFERENCIAS|BIBLIOGRAPHY|BIBLIOGRAFIA)\b/iu', $textoPdf, $m) === 1) {
                $metadados['marcador_inicio_referencias'] = $m[1];
            }
        }

        if ($this->campoVazio($metadados['slug'] ?? null) && ! $this->campoVazio($metadados['titulo'] ?? null)) {
            $metadados['slug'] = str($metadados['titulo'])->slug()->toString();
        }

        if ($this->campoVazio($metadados['seo_titulo'] ?? null) && ! $this->campoVazio($metadados['titulo'] ?? null)) {
            $metadados['seo_titulo'] = (string) $metadados['titulo'];
        }

        if ($this->campoVazio($metadados['seo_descricao'] ?? null) && ! $this->campoVazio($metadados['resumo'] ?? null)) {
            $resumoPlano = trim(html_entity_decode(strip_tags((string) $metadados['resumo']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $metadados['seo_descricao'] = mb_substr($resumoPlano, 0, 160);
        }

        if ($this->campoVazio($metadados['seo_palavras_chave'] ?? null) && ! $this->listaVazia($metadados['palavras_chave'] ?? null)) {
            $metadados['seo_palavras_chave'] = implode(', ', $metadados['palavras_chave']);
        }

        return $metadados;
    }

    private function extrairTitulo(string $inicio): ?string
    {
        if (preg_match(
            '/\b(?:RESEARCH ARTICLE|ORIGINAL ARTICLE|REVIEW|EDITORIAL|ARTICLE)\b\s+(.+?)(?=\n\s*[A-Z][a-záéíóúâêôãõç]+ [A-ZÁÉÍÓÚÂÊÔÃÕÇ])/su',
            $inicio,
            $m,
        ) === 1) {
            $titulo = $this->compactar($m[1]);

            if (mb_strlen($titulo) >= 20) {
                return $titulo;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function extrairAutores(string $inicio): array
    {
        // Nomes próprios seguidos de índice de afiliação em linha própria (layout Memórias).
        if (preg_match_all(
            '/(?:,\s*)?([A-ZÁÉÍÓÚÂÊÔÃÕÇ][a-záéíóúâêôãõç]+(?:\s+[A-ZÁÉÍÓÚÂÊÔÃÕÇ][a-záéíóúâêôãõç]+){1,4})\s*\n\s*[\d,\/\+]+/u',
            $inicio,
            $matches,
        ) === 0) {
            return [];
        }

        $autores = [];

        foreach ($matches[1] as $nome) {
            $nome = trim($nome);

            if ($nome === '' || mb_strlen($nome) < 5) {
                continue;
            }

            if (preg_match('/\b(institut|universit|fundação|fundacao|laborat|france|brasil|brazil)\b/iu', $nome) === 1) {
                continue;
            }

            $autores[] = $nome;
        }

        return array_values(array_unique($autores));
    }

    private function extrairInstituicoes(string $inicio): ?string
    {
        // Mantém o bloco literal do PDF (números + afiliações) para validação de fidelidade.
        if (preg_match(
            '/\n(\s*\d{1,2}\s*\n\s*(?:.|\n)*?)(?=\nChagas disease|\nAbstract\b|\nResumo\b|\nKey words?:)/iu',
            $inicio,
            $m,
        ) !== 1) {
            return null;
        }

        $bloco = trim($m[1]);

        return $bloco !== '' ? $bloco : null;
    }

    private function extrairResumo(string $inicio): ?string
    {
        // Abstract estruturado do portal atual: BACKGROUND / OBJECTIVES / METHODS / FINDINGS / MAIN CONCLUSIONS.
        if (preg_match(
            '/\bBACKGROUND\b\s*(.+?)(?=\nKey words?:|\nKeywords?:)/isu',
            $inicio,
            $m,
        ) === 1) {
            $candidato = $this->compactar('BACKGROUND '.$m[1]);

            if (preg_match('/\b(OBJECTIVES?|METHODS|FINDINGS|MAIN CONCLUSIONS?)\b/u', $candidato) === 1
                && mb_strlen($candidato) >= 80) {
                return $candidato;
            }
        }

        if (preg_match(
            '/(?:France|Brasil|Brazil|USA|Argentina|Chile|Peru|Mexico|México|Portugal|Spain|España)\s*\n+([A-Z].+?)(?=\nKey words?:|\nKeywords?:)/isu',
            $inicio,
            $m,
        ) === 1) {
            $candidato = $this->compactar($m[1]);

            if (mb_strlen($candidato) >= 80 && ! preg_match('/^(doi:|Financial support)/iu', $candidato)) {
                return $candidato;
            }
        }

        if (preg_match('/\bAbstract[:\s]+(.+?)(?=\nKey words?:|\nKeywords?:)/isu', $inicio, $m) === 1) {
            return $this->compactar($m[1]);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function extrairPalavrasChave(string $inicio): array
    {
        if (preg_match('/Key words?:\s*(.+)/iu', $inicio, $m) !== 1) {
            return [];
        }

        $linha = trim($m[1]);
        $linha = preg_replace('/\s+/u', ' ', $linha) ?? $linha;
        $partes = preg_split('/\s+-\s+/u', $linha) ?: [];

        return array_values(array_filter(array_map('trim', $partes), static fn (string $p): bool => $p !== ''));
    }

    private function extrairDoi(string $texto): ?string
    {
        if (preg_match('/\bdoi:\s*(10\.\d{4,}\/[^\n]+)/iu', $texto, $m) !== 1) {
            return null;
        }

        $doi = preg_replace('/\s+/u', '', $m[1]) ?? $m[1];
        $doi = rtrim($doi, '.;,');

        return $doi !== '' ? $doi : null;
    }

    private function extrairAutorCorrespondente(string $texto): ?string
    {
        // Bloco literal: financial support → Accepted (antes do corpo).
        if (preg_match(
            '/Financial support:\s*(.+?Accepted\s+\d{1,2}\s+\w+\s+\d{4})/isu',
            $texto,
            $m,
        ) === 1) {
            return trim('Financial support: '.$m[1]);
        }

        if (preg_match('/Corresponding author:\s*([^\n]+)/iu', $texto, $m) === 1) {
            return trim($m[1]);
        }

        return null;
    }

    private function extrairMarcadorCorpo(string $texto): ?string
    {
        // Layout clássico do portal: corpo editorial completo após Accepted.
        if (preg_match('/Accepted\s+\d{1,2}\s+\w+\s+\d{4}\s*(.+)/isu', $texto, $m) === 1) {
            $depois = $this->compactar($m[1]);

            if (preg_match('/^(How to cite|Handling editor)/iu', $depois) !== 1) {
                $marcador = $this->janelaMarcador($depois);

                if ($marcador !== null) {
                    return $marcador;
                }
            }
        }

        // Layout atual: prosa começa imediatamente após Key words.
        if (preg_match('/(?:Key words?|Keywords?)\s*:[^\n]+\s*(.+)/isu', $texto, $m) === 1) {
            $depois = $this->compactar($m[1]);

            if (preg_match('/^(doi\s*:|Financial support|Received\b|Accepted\b|How to cite|Handling editor)/iu', $depois) !== 1) {
                return $this->janelaMarcador($depois);
            }
        }

        return null;
    }

    private function janelaMarcador(string $depois): ?string
    {
        $palavras = preg_split('/\s+/u', $depois) ?: [];
        $marcador = trim(implode(' ', array_slice($palavras, 0, 12)));

        return mb_strlen($marcador) >= 24 ? $marcador : null;
    }

    private function doiPareceQuebrado(string $doi): bool
    {
        $limpo = preg_replace('/\s+/u', '', $doi) ?? $doi;

        return $limpo === ''
            || preg_match('/^10\.\d{4,}\/\S+$/u', $limpo) !== 1
            || str_contains($doi, ' ');
    }

    private function campoVazio(mixed $valor): bool
    {
        return trim((string) ($valor ?? '')) === '';
    }

    private function listaVazia(mixed $valor): bool
    {
        if (! is_array($valor)) {
            return true;
        }

        foreach ($valor as $item) {
            if (trim((string) $item) !== '') {
                return false;
            }
        }

        return true;
    }

    private function compactar(string $texto): string
    {
        $texto = preg_replace('/(\p{L})-\s+(\p{Ll})/u', '$1$2', $texto) ?? $texto;
        $texto = preg_replace("/[ \t]*\n[ \t]*/u", ' ', $texto) ?? $texto;
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }
}
