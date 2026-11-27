<?php

namespace App\Support\Artigos;

/**
 * Monta table HTML a partir do texto extraído de páginas de tabela
 * landscape/rotacionadas do PDF (campos tipicamente um por linha).
 */
class MontarHtmlTabelaDeTextoPdf
{
    public function __construct(
        private readonly RepararEspacamentoLetrasDeTextoPdf $repararEspacamento = new RepararEspacamentoLetrasDeTextoPdf,
    ) {}

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
     * Gêneros de Triatominae desta TABLE I (e afins). Evita tratar
     * plantas de ecotope (Attalea, Oenocarpus) ou marcadores DNA (Cytb) como Genus.
     */
    private const GENEROS = [
        'Alberprosenia', 'Belminus', 'Cavernicola', 'Dipetalogaster', 'Eratyrus',
        'Hermanlentia', 'Linshcosteus', 'Meccus', 'Mepraia', 'Nesotriatoma',
        'Paleotriatoma', 'Panstrongylus', 'Paratriatoma', 'Psammolestes',
        'Rhodnius', 'Triatoma', 'Torrealbaia',
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
            foreach ($this->linhasUteis($this->repararEspacamento->reparar($texto)) as $linha) {
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

        $html = '<div class="tabela-pdf-scroll"><table><caption>'.e($caption).'</caption><thead><tr>';

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

        return $html.'</tbody></table></div>';
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

            if ($this->pareceGenusConhecido($token)) {
                $genusAtual = $token;
                $i++;

                continue;
            }

            if ($this->pareceDna($token)) {
                if ($linhas !== []) {
                    $ultimo = array_key_last($linhas);
                    if (trim($linhas[$ultimo]['DNA sequence']) === '') {
                        $linhas[$ultimo]['DNA sequence'] = $token;
                    } elseif (! str_contains($linhas[$ultimo]['DNA sequence'], $token)) {
                        $linhas[$ultimo]['DNA sequence'] = trim($linhas[$ultimo]['DNA sequence'].' '.$token);
                    }
                }
                $i++;

                continue;
            }

            if ($genusAtual === '') {
                $i++;

                continue;
            }

            if (! $this->pareceEpithetSpecies($token)) {
                if ($linhas !== [] && $this->pareceContinuacaoEcotope($token)) {
                    $ultimo = array_key_last($linhas);
                    $linhas[$ultimo]['Ecotope'] = trim($linhas[$ultimo]['Ecotope'].' '.$token);
                }
                $i++;

                continue;
            }

            $species = $token;
            $i++;

            while ($i < $total && preg_match('/^\*+$/u', $dados[$i]) === 1) {
                $species .= $dados[$i];
                $i++;
            }

            // Subespécie / epíteto composto: "brasiliensis macromelasoma**"
            if ($i < $total && $this->pareceSegundoEpithet($dados[$i], $dados[$i + 1] ?? '')) {
                $species .= ' '.$dados[$i];
                $i++;
                while ($i < $total && preg_match('/^\*+$/u', $dados[$i]) === 1) {
                    $species .= $dados[$i];
                    $i++;
                }
            }

            $authorParts = [];
            while ($i < $total && ! $this->pareceAno($dados[$i])) {
                if ($this->pareceGenusConhecido($dados[$i]) || $this->pareceEpithetSpecies($dados[$i])) {
                    break;
                }
                if ($this->pareceDna($dados[$i])) {
                    break;
                }
                $authorParts[] = $dados[$i];
                $i++;
                if (count($authorParts) > 12) {
                    break;
                }
            }

            $year = ($i < $total && $this->pareceAno($dados[$i])) ? $dados[$i] : '';
            if ($year !== '') {
                $i++;
            }

            $rest = [];
            while ($i < $total) {
                if ($this->pareceGenusConhecido($dados[$i]) || $this->pareceEpithetSpecies($dados[$i])) {
                    break;
                }
                $rest[] = $dados[$i];
                $i++;
            }

            if (trim($species) === '' || $year === '') {
                continue;
            }

            [$locality, $distribution, $collection, $ecotope, $dna] = $this->repartirCamposFinais($rest);

            $linhas[] = [
                'Genus' => $genusAtual,
                'Species' => $species,
                'Author' => trim(implode(' ', $authorParts)),
                'Year' => $year,
                'Type locality' => $locality,
                'Distribution' => $distribution,
                'Collection (Type)' => $collection,
                'Ecotope' => $ecotope,
                'DNA sequence' => $dna,
            ];
        }

        return $linhas;
    }

    /**
     * @param  list<string>  $rest
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string}
     */
    private function repartirCamposFinais(array $rest): array
    {
        $dnaParts = [];
        while ($rest !== []) {
            $last = $rest[array_key_last($rest)];
            if ($this->pareceDna($last) || ($last === '-' && $dnaParts === [])) {
                array_unshift($dnaParts, (string) array_pop($rest));

                continue;
            }
            break;
        }
        $dna = trim(implode(' ', $dnaParts));

        $ecotopeParts = [];
        while ($rest !== []) {
            $last = $rest[array_key_last($rest)];
            if ($this->pareceEcotopeToken($last) || $this->pareceContinuacaoEcotope($last)) {
                array_unshift($ecotopeParts, (string) array_pop($rest));

                continue;
            }
            if (preg_match('/\b(altitude|desert|area|cave|rocks?|tree|dwellings?|ambar|amber|reserve|palm|coastal|formation)\b/iu', $last) === 1) {
                array_unshift($ecotopeParts, (string) array_pop($rest));

                continue;
            }
            break;
        }
        $ecotope = trim(implode(' ', $ecotopeParts));

        $localityParts = [];
        while ($rest !== []) {
            $localityParts[] = (string) array_shift($rest);
            if (preg_match('/\(\d+\)\s*$/u', implode(' ', $localityParts)) === 1) {
                break;
            }
            if (count($localityParts) >= 4) {
                break;
            }
        }
        $locality = trim(implode(' ', $localityParts));

        $distribution = '';
        if ($rest !== []) {
            $distribution = (string) array_shift($rest);
            while ($rest !== [] && ! $this->pareceInicioColecao($rest[0])) {
                $proximo = $rest[0];
                if ($this->pareceEcotopeToken($proximo) || $this->pareceDna($proximo)) {
                    break;
                }
                // Continuação geográfica curta (ex.: segunda linha de país/província).
                if (preg_match('/^[A-Z(]/u', $proximo) === 1 && ! $this->pareceInicioColecao($proximo) && mb_strlen($distribution) < 120) {
                    $distribution .= ' '.array_shift($rest);

                    continue;
                }
                break;
            }
        }

        $collection = trim(implode(' ', $rest));

        return [$locality, trim($distribution), $collection, $ecotope, $dna];
    }

    private function pareceGenusConhecido(string $token): bool
    {
        foreach (self::GENEROS as $genero) {
            if (strcasecmp($token, $genero) === 0) {
                return true;
            }
        }

        return false;
    }

    private function pareceEpithetSpecies(string $token): bool
    {
        if ($this->pareceDna($token) || $this->pareceAno($token)) {
            return false;
        }

        if (preg_match('/^(and|or|of|the|in|near|from|with|under|at|on)$/iu', $token) === 1) {
            return false;
        }

        return preg_match('/^[a-z][a-z\-]*\**$/u', $token) === 1;
    }

    private function pareceSegundoEpithet(string $token, string $seguinte): bool
    {
        if (preg_match('/^[a-z][a-z\-]*\*+$/u', $token) === 1) {
            return true;
        }

        if (preg_match('/^[a-z][a-z\-]+$/u', $token) !== 1) {
            return false;
        }

        // "macromelasoma" + "**" na linha seguinte
        return preg_match('/^\*+$/u', $seguinte) === 1;
    }

    private function pareceAno(string $token): bool
    {
        // Triatominae TABLE I inclui espécies do séc. XIX (ex.: 1811, 1873).
        return preg_match('/^(18|19|20)\d{2}$/u', $token) === 1;
    }

    private function pareceDna(string $token): bool
    {
        $token = trim($token);

        if ($token === '') {
            return false;
        }

        // Marcadores isolados ou listas: "Cytb", "Cytb, 28S", "28S, 16S, 18S", "ITS-2 Cytb"
        return preg_match(
            '/^(?:-|(?:18S|28S|16S|Cytb|COI|COII|ITS-?[12])(?:\s*,\s*(?:18S|28S|16S|Cytb|COI|COII|ITS-?[12]))*)$/iu',
            $token,
        ) === 1
            || preg_match('/^(?:18S|28S|16S|Cytb|COI|COII|ITS-?[12])(?:\s+(?:18S|28S|16S|Cytb|COI|COII|ITS-?[12]))+$/iu', $token) === 1;
    }

    private function pareceEcotopeToken(string $token): bool
    {
        if ($token === '-') {
            return false;
        }

        $curtos = [
            'Dwellings', 'Hollow tree', 'High altitude', 'Near a cave', 'Rock formation',
            'Amber', 'Ambar', 'Palm tree', 'Coastal desert', 'Coastal area', 'Rocks',
            'Under rocks', 'Rock outcrops', 'Domiciliary ecotopes',
        ];

        foreach ($curtos as $eco) {
            if (strcasecmp($token, $eco) === 0) {
                return true;
            }
        }

        return preg_match('/^(Attalea|Oenocarpus|Maximilian|Orbignya|Mauritia)\b/iu', $token) === 1;
    }

    private function pareceContinuacaoEcotope(string $token): bool
    {
        if (preg_match('/^(and|or)$/iu', $token) === 1) {
            return true;
        }

        return $this->pareceEcotopeToken($token)
            || preg_match('/^(butyracea|bataua|speciosa|regia|phalerata|bacaba)\b/iu', $token) === 1;
    }

    private function pareceInicioColecao(string $token): bool
    {
        return preg_match('/\b(Collection|Colecci[oó]n|Museum|Museo|Instituto|Faculdade|Department|Herman|Rodolfo|Natural History|INPA|Ponair)\b/iu', $token) === 1;
    }

    private function formatarCelula(string $cabecalho, string $valor): string
    {
        $valor = trim($this->repararEspacamento->reparar(trim($valor)));

        if ($valor === '') {
            return '';
        }

        if (in_array($cabecalho, ['Genus', 'Species'], true)) {
            if (preg_match('/^(.+?)(\*+)$/u', $valor, $m) === 1) {
                return '<em>'.e($m[1]).'</em>'.e($m[2]);
            }

            return '<em>'.e($valor).'</em>';
        }

        return e($valor);
    }
}
