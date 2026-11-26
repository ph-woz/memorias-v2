<?php

namespace App\Support\Artigos;

/**
 * Monta table HTML a partir do texto extraído de páginas de tabela
 * landscape/rotacionadas do PDF (campos tipicamente um por linha).
 */
class MontarHtmlTabelaDeTextoPdf
{
    private const CABECALHOS = [
        'Genus',
        'Species',
        'Author',
        'Year',
        'Type locality',
        'Distribution',
        'Collection (Type)',
        'Ecotope',
        'DNA sequence',
    ];

    /**
     * @param  list<string>  $textosPaginas
     */
    public function dePaginas(array $textosPaginas, string $caption = 'TABLE I'): string
    {
        if ($textosPaginas === []) {
            return '';
        }

        $linhas = [];

        foreach ($textosPaginas as $texto) {
            foreach ($this->linhasUteis($texto) as $linha) {
                $linhas[] = $linha;
            }
        }

        $inicio = $this->indiceCabecalhos($linhas);

        if ($inicio === null) {
            return '';
        }

        $dados = array_slice($linhas, $inicio + count(self::CABECALHOS));
        $linhasTabela = $this->parsearLinhas($dados);

        if ($linhasTabela === []) {
            return '';
        }

        $html = '<table><caption>'.e($caption).'</caption><thead><tr>';

        foreach (self::CABECALHOS as $cabecalho) {
            $html .= '<th>'.e($cabecalho).'</th>';
        }

        $html .= '</tr></thead><tbody>';

        foreach ($linhasTabela as $linha) {
            $html .= '<tr>';
            foreach (self::CABECALHOS as $cabecalho) {
                $valor = $linha[$cabecalho] ?? '';
                $html .= '<td>'.$this->formatarCelula($cabecalho, $valor).'</td>';
            }
            $html .= '</tr>';
        }

        return $html.'</tbody></table>';
    }

    /**
     * @return list<string>
     */
    private function linhasUteis(string $texto): array
    {
        $brutas = preg_split('/\R/u', $texto) ?: [];
        $linhas = [];

        foreach ($brutas as $linha) {
            $linha = trim(html_entity_decode($linha, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $linha = trim(preg_replace('/\s+/u', ' ', $linha) ?? $linha);

            if ($linha === '') {
                continue;
            }

            if ($this->linhaRuido($linha)) {
                continue;
            }

            if (preg_match('/^ye\s*a\s*r$/iu', $linha) === 1) {
                $linha = 'Year';
            }

            $linhas[] = $linha;
        }

        return $linhas;
    }

    private function linhaRuido(string $linha): bool
    {
        if (preg_match('/^\d+\s*\|\s*\d+$/u', $linha) === 1) {
            return true;
        }

        if (preg_match('/^Mem Inst Oswaldo Cruz/iu', $linha) === 1) {
            return true;
        }

        if (preg_match('/et al\.\s*\d+\s*\|\s*\d+/iu', $linha) === 1) {
            return true;
        }

        if (preg_match('/^TABLE$/iu', $linha) === 1) {
            return true;
        }

        if (preg_match('/^Species published after/iu', $linha) === 1) {
            return true;
        }

        if (preg_match('/^based on Galv/iu', $linha) === 1) {
            return true;
        }

        if (preg_match('/^and updated/iu', $linha) === 1) {
            return true;
        }

        if (preg_match('/^\(\d+\)$/u', $linha) === 1) {
            return true;
        }

        if (preg_match('/^\*+\s*fossil/iu', $linha) === 1) {
            return true;
        }

        if (preg_match('/^\*+\s*revalidated/iu', $linha) === 1) {
            return true;
        }

        return false;
    }

    /**
     * @param  list<string>  $linhas
     */
    private function indiceCabecalhos(array $linhas): ?int
    {
        $total = count($linhas);

        for ($i = 0; $i < $total; $i++) {
            if (strcasecmp($linhas[$i], 'Genus') !== 0) {
                continue;
            }

            $janela = array_slice($linhas, $i, 12);
            $achados = 0;

            foreach (['Species', 'Author', 'Year', 'Type locality', 'Distribution', 'Ecotope', 'DNA sequence'] as $esperado) {
                foreach ($janela as $candidato) {
                    if (strcasecmp($candidato, $esperado) === 0
                        || (strcasecmp($esperado, 'Year') === 0 && preg_match('/^ye\s*a\s*r$/iu', $candidato) === 1)) {
                        $achados++;
                        break;
                    }
                }
            }

            if ($achados >= 5) {
                return $i;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $dados
     * @return list<array<string, string>>
     */
    private function parsearLinhas(array $dados): array
    {
        $linhas = [];
        $genusAtual = '';
        $i = 0;
        $total = count($dados);

        while ($i < $total) {
            $token = $dados[$i];

            if ($this->pareceGenus($token, $dados, $i)) {
                $genusAtual = $token;
                $i++;

                continue;
            }

            if ($genusAtual === '') {
                $i++;

                continue;
            }

            $species = $token;
            $i++;

            // asteriscos de nota (**) eventualmente na linha seguinte
            while ($i < $total && preg_match('/^\*+$/u', $dados[$i]) === 1) {
                $species .= $dados[$i];
                $i++;
            }

            $authorParts = [];
            while ($i < $total && ! $this->pareceAno($dados[$i])) {
                if ($this->pareceGenus($dados[$i], $dados, $i) && $authorParts !== []) {
                    break;
                }
                $authorParts[] = $dados[$i];
                $i++;
                if (count($authorParts) > 8) {
                    break;
                }
            }

            $year = ($i < $total && $this->pareceAno($dados[$i])) ? $dados[$i] : '';
            if ($year !== '') {
                $i++;
            }

            $locality = $i < $total ? $dados[$i] : '';
            if ($locality !== '') {
                $i++;
            }

            $distribution = $i < $total ? $dados[$i] : '';
            if ($distribution !== '') {
                $i++;
            }

            $collectionParts = [];
            while ($i < $total && ! $this->pareceEcotopeOuDna($dados[$i], $dados, $i) && ! $this->pareceGenus($dados[$i], $dados, $i) && ! $this->pareceSpeciesSeguinte($dados[$i], $dados, $i)) {
                $collectionParts[] = $dados[$i];
                $i++;
                if (count($collectionParts) > 6) {
                    break;
                }
            }

            $ecotope = '';
            $dna = '';
            if ($i < $total && ! $this->pareceGenus($dados[$i], $dados, $i) && ! $this->pareceSpeciesSeguinte($dados[$i], $dados, $i)) {
                $ecotope = $dados[$i];
                $i++;
            }
            if ($i < $total && ! $this->pareceGenus($dados[$i], $dados, $i) && ! $this->pareceSpeciesSeguinte($dados[$i], $dados, $i) && ! $this->pareceAno($dados[$i])) {
                // se o que sobrou parece DNA (curto) e o ecotope já pegou texto longo, ok
                if ($this->pareceDna($dados[$i]) || $ecotope !== '') {
                    $dna = $dados[$i];
                    $i++;
                }
            }

            if (trim($species) === '' || $year === '') {
                continue;
            }

            $linhas[] = [
                'Genus' => $genusAtual,
                'Species' => $species,
                'Author' => trim(implode(' ', $authorParts)),
                'Year' => $year,
                'Type locality' => $locality,
                'Distribution' => $distribution,
                'Collection (Type)' => trim(implode(' ', $collectionParts)),
                'Ecotope' => $ecotope,
                'DNA sequence' => $dna,
            ];
        }

        return $linhas;
    }

    /**
     * @param  list<string>  $dados
     */
    private function pareceGenus(string $token, array $dados, int $indice): bool
    {
        if (preg_match('/^[A-Z][a-z]+$/u', $token) !== 1) {
            return false;
        }

        if (in_array($token, self::CABECALHOS, true)) {
            return false;
        }

        // Genus seguido de species (minúscula) ou de outra linha taxonômica
        $prox = $dados[$indice + 1] ?? '';

        return $prox !== '' && preg_match('/^[a-z]/u', $prox) === 1;
    }

    /**
     * @param  list<string>  $dados
     */
    private function pareceSpeciesSeguinte(string $token, array $dados, int $indice): bool
    {
        if (preg_match('/^[a-z][a-z\-]*\**$/u', $token) !== 1) {
            return false;
        }

        $prox = $dados[$indice + 1] ?? '';

        // species seguida de author (não ano imediato)
        return $prox !== '' && ! $this->pareceAno($prox) && preg_match('/^[A-Z]/u', $prox) === 1;
    }

    private function pareceAno(string $token): bool
    {
        return preg_match('/^(19|20)\d{2}$/u', $token) === 1;
    }

    /**
     * @param  list<string>  $dados
     */
    private function pareceEcotopeOuDna(string $token, array $dados, int $indice): bool
    {
        if ($this->pareceDna($token)) {
            return true;
        }

        if ($token === '-') {
            // "-" pode ser ecotope ou dna; se o próximo também é dna/-, trata como ecotope boundary
            return true;
        }

        $curtos = ['Dwellings', 'Hollow tree', 'High altitude', 'Near a cave', 'Rock formation', 'Amber', 'Palm tree'];

        foreach ($curtos as $eco) {
            if (strcasecmp($token, $eco) === 0) {
                return true;
            }
        }

        return false;
    }

    private function pareceDna(string $token): bool
    {
        if ($token === '-') {
            return true;
        }

        return preg_match('/^(18S|28S|16S|Cytb|COI|ITS-?2|COII)(\s*,\s*(18S|28S|16S|Cytb|COI|ITS-?2|COII))*$/iu', $token) === 1;
    }

    private function formatarCelula(string $cabecalho, string $valor): string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return '';
        }

        if (in_array($cabecalho, ['Genus', 'Species'], true)) {
            // species pode ter ** no fim
            if (preg_match('/^(.+?)(\*+)$/u', $valor, $m) === 1) {
                return '<em>'.e($m[1]).'</em>'.e($m[2]);
            }

            return '<em>'.e($valor).'</em>';
        }

        return e($valor);
    }
}
