<?php

namespace App\Support\Artigos;

/**
 * Enriquece HTML literal/IA do PDF com tipografia científica
 * (parágrafos, itálicos, negritos, sobrescritos, imagens).
 *
 * Seguro para rodar sempre — inclusive sobre HTML já rico da IA —
 * sem destruir tags existentes.
 */
class EnriquecerHtmlLiteralDoPdf
{
    /**
     * @param  list<array{path: string, url: string, alt?: string}>  $imagens
     */
    public function enriquecer(string $html, array $imagens = []): string
    {
        return $this->polir($html, $imagens);
    }

    /**
     * Polimento final (Gemini ou fallback): tipografia + parágrafos + imagens.
     *
     * @param  list<array{path: string, url: string, alt?: string}>  $imagens
     */
    public function polir(string $html, array $imagens = []): string
    {
        $html = trim($html);

        if ($html === '') {
            return $html;
        }

        $html = $this->normalizarFiguras($html);
        $html = $this->desifenizarHtml($html);

        if (! str_contains($html, '<')) {
            $html = $this->textoParaParagrafos($html);
        }

        $html = $this->garantirParagrafos($html);
        $html = $this->repartirParagrafosLongos($html);
        $html = $this->aplicarTipografiaPreservandoTags($html);
        $html = $this->inserirImagens($html, $imagens);

        return trim($html);
    }

    /**
     * Mesmo com HTML vindo da IA, evita "parede de texto" repartindo <p> longos.
     */
    public function repartirParagrafosLongos(string $html, int $limite = 520): string
    {
        return (string) preg_replace_callback(
            '/<p\b([^>]*)>(.*?)<\/p>/is',
            function (array $m) use ($limite): string {
                $attrs = $m[1];
                $interno = $m[2];

                if (str_contains(strtolower($interno), '<img')) {
                    return $m[0];
                }

                $texto = html_entity_decode(strip_tags($interno), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);

                if (mb_strlen($texto) <= $limite) {
                    return $m[0];
                }

                // Sem tags internas: reparte e reescapa.
                if (! str_contains($interno, '<')) {
                    $html = '';

                    foreach ($this->partirBlocoLongo($texto) as $parte) {
                        $html .= '<p'.$attrs.'>'.e($parte).'</p>';
                    }

                    return $html;
                }

                // Com tipografia: reparte preservando tags ao redor de frases.
                return $this->repartirInternoComTags($attrs, $interno, $limite);
            },
            $html,
        );
    }

    /**
     * @param  list<array{path: string, url: string, alt?: string}>  $imagens
     */
    public function injetarImagensSeAusentes(string $html, array $imagens): string
    {
        return $this->inserirImagens($html, $imagens);
    }

    /**
     * Tipografia rica para campos curtos (resumo, autor correspondente, instituições)
     * sem repartir parágrafos longos nem injetar imagens do PDF.
     */
    public function polirCampoRico(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return $html;
        }

        $html = $this->desifenizarHtml($html);

        if (! str_contains($html, '<')) {
            $html = $this->textoParaParagrafos($html);
        }

        $html = $this->garantirParagrafos($html);

        return trim($this->aplicarTipografiaPreservandoTags($html));
    }

    private function normalizarFiguras(string $html): string
    {
        $html = (string) preg_replace(
            '/<figure\b[^>]*>\s*(<img\b[^>]*>)\s*(?:<figcaption\b[^>]*>.*?<\/figcaption>)?\s*<\/figure>/is',
            '<p style="text-align:center">$1</p>',
            $html,
        );

        return (string) preg_replace('/<\/?figcaption\b[^>]*>/i', '', $html);
    }

    private function desifenizarHtml(string $html): string
    {
        return $this->mapearSomenteTexto($html, function (string $texto): string {
            $texto = str_replace("\u{00AD}", '', $texto);
            $texto = preg_replace('/(\p{L})-\R(\p{Ll})/u', '$1$2', $texto) ?? $texto;
            $texto = preg_replace('/(\p{L})-\s+(\p{Ll})/u', '$1$2', $texto) ?? $texto;
            $texto = preg_replace('/(\p{L})-\R(\p{Lu})/u', '$1-$2', $texto) ?? $texto;
            $texto = preg_replace('/(\p{L})-\s+(\p{Lu})/u', '$1-$2', $texto) ?? $texto;

            return $texto;
        });
    }

    private function textoParaParagrafos(string $texto): string
    {
        $texto = trim($texto);
        $blocos = preg_split("/\n{2,}/u", $texto) ?: [$texto];
        $html = [];

        foreach ($blocos as $bloco) {
            $bloco = trim(preg_replace("/[ \t]*\n[ \t]*/u", ' ', $bloco) ?? $bloco);

            if ($bloco === '') {
                continue;
            }

            foreach ($this->partirBlocoLongo($bloco) as $parte) {
                $html[] = '<p>'.e($parte).'</p>';
            }
        }

        return implode('', $html);
    }

    private function garantirParagrafos(string $html): string
    {
        if (preg_match('/<(p|h2|h3|ul|ol|blockquote)\b/i', $html) === 1) {
            return $html;
        }

        return $this->textoParaParagrafos(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function aplicarTipografiaPreservandoTags(string $html): string
    {
        return (string) preg_replace_callback(
            '/<(p|h2|h3)\b([^>]*)>(.*?)<\/\1>/is',
            function (array $m): string {
                $tag = strtolower($m[1]);
                $attrs = $m[2];
                $interno = $m[3];

                if (str_contains(strtolower($interno), '<img')) {
                    return $m[0];
                }

                $texto = trim(html_entity_decode(strip_tags($interno), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);

                if ($texto === '') {
                    return $m[0];
                }

                if ($tag === 'p' && $this->pareceTituloSecao($texto)) {
                    return '<h2><strong>'.e($texto).'</strong></h2>';
                }

                if ($tag === 'h2' && ! str_contains($interno, '<strong')) {
                    return '<h2'.$attrs.'><strong>'.e($texto).'</strong></h2>';
                }

                if ($tag === 'p' && $this->pareceSubtituloNegrito($texto) && ! str_contains($interno, '<strong')) {
                    return '<p'.$attrs.'><strong>'.e($texto).'</strong></p>';
                }

                $rico = $this->mapearTextoForaDeTipografia($interno, function (string $chunk): string {
                    $chunk = $this->marcarCitacoesEmTexto($chunk);
                    $chunk = $this->marcarEspeciesEmTexto($chunk);
                    $chunk = $this->marcarFigurasETabelas($chunk);

                    return $chunk;
                });

                return '<'.$tag.$attrs.'>'.$rico.'</'.$tag.'>';
            },
            $html,
        );
    }

    private function repartirInternoComTags(string $attrs, string $interno, int $limite): string
    {
        $partes = [];
        $atual = '';
        $textoAtual = '';
        $len = strlen($interno);
        $i = 0;

        while ($i < $len) {
            if ($interno[$i] === '<') {
                $fecha = strpos($interno, '>', $i);
                if ($fecha === false) {
                    $atual .= substr($interno, $i);
                    break;
                }
                $atual .= substr($interno, $i, $fecha - $i + 1);
                $i = $fecha + 1;

                continue;
            }

            $proxTag = strpos($interno, '<', $i);
            $trecho = $proxTag === false ? substr($interno, $i) : substr($interno, $i, $proxTag - $i);
            $i = $proxTag === false ? $len : $proxTag;

            $frases = preg_split('/(?<=[.!?])\s+/u', $trecho) ?: [$trecho];

            foreach ($frases as $idx => $frase) {
                if ($frase === '') {
                    continue;
                }

                $separador = ($idx > 0 || $textoAtual !== '') && ! str_ends_with($atual, ' ') ? ' ' : '';
                $candidatoTexto = trim($textoAtual.' '.$frase);
                $candidatoHtml = $atual.$separador.$frase;

                if (mb_strlen($candidatoTexto) > $limite && $textoAtual !== '') {
                    $partes[] = $atual;
                    $atual = $frase;
                    $textoAtual = trim(strip_tags($frase));

                    continue;
                }

                $atual = $candidatoHtml;
                $textoAtual = $candidatoTexto;
            }
        }

        if (trim(strip_tags($atual)) !== '') {
            $partes[] = $atual;
        }

        if (count($partes) <= 1) {
            return '<p'.$attrs.'>'.$interno.'</p>';
        }

        $html = '';

        foreach ($partes as $parte) {
            $html .= '<p'.$attrs.'>'.trim($parte).'</p>';
        }

        return $html;
    }

    /**
     * @return list<string>
     */
    private function partirBlocoLongo(string $texto): array
    {
        if (mb_strlen($texto) <= 480) {
            return [$texto];
        }

        $frases = preg_split('/(?<=[.!?])\s+(?=[A-ZÁÉÍÓÚÂÊÔÃÕ“"])/u', $texto) ?: [$texto];
        $partes = [];
        $atual = '';

        foreach ($frases as $frase) {
            $frase = trim($frase);

            if ($frase === '') {
                continue;
            }

            $candidato = $atual === '' ? $frase : $atual.' '.$frase;

            if (mb_strlen($candidato) > 450 && $atual !== '') {
                $partes[] = $atual;
                $atual = $frase;

                continue;
            }

            $atual = $candidato;
        }

        if ($atual !== '') {
            $partes[] = $atual;
        }

        return $partes !== [] ? $partes : [$texto];
    }

    private function pareceTituloSecao(string $texto): bool
    {
        if (str_ends_with($texto, '.') || mb_strlen($texto) > 90) {
            return false;
        }

        if (preg_match('/^(Introduction|Methods|Results|Discussion|Conclusions?|Background|Objectives?|Findings|Main conclusions?|Material(?:s)? and methods?|Acknowledgements?|Authors(?:[’\'] contribution)?|In conclusion)\b/iu', $texto) === 1) {
            return true;
        }

        if (preg_match('/\b(Republic|Guiana|States|America|Brasil|Brazil|Argentina|Mexico|Chile|Peru|France|Spain)\b/i', $texto) === 1) {
            return false;
        }

        return str_word_count($texto) <= 8
            && substr_count($texto, ',') === 0
            && preg_match('/^[A-Z][A-Za-z0-9 \-]+$/', $texto) === 1
            && preg_match('/\b(the|and|of|in|to|for|with|from|that|this|are|was|were)\b/i', $texto) !== 1
            && preg_match('/\b(until|after|before|species|vectors|control|diagnosis|treatment|surveillance|challenges|strategies|overview)\b/i', $texto) === 1;
    }

    private function pareceSubtituloNegrito(string $texto): bool
    {
        if (mb_strlen($texto) > 70 || str_ends_with($texto, '.')) {
            return false;
        }

        return preg_match('/^(New species|Taxonomic|Epidemiolog|Vector|Control|Diagnosis|Treatment|Surveillance|Challenges|Strategies)\b/iu', $texto) === 1;
    }

    private function marcarCitacoesEmTexto(string $textoEscapado): string
    {
        // Evita remarcar o que já está em <sup>.
        if (str_contains($textoEscapado, '<sup')) {
            return (new NormalizarCitacoesNoHtml)->normalizar($textoEscapado);
        }

        // Só marca listas de citação (1–3 dígitos). Não captura anos tipo (1859).
        $marcado = (string) preg_replace(
            '/\((\d{1,3}(?:\s*,\s*\d{1,3})*)\)/u',
            '<sup>($1)</sup>',
            $textoEscapado,
        );

        return (new NormalizarCitacoesNoHtml)->normalizar($marcado);
    }

    private function marcarEspeciesEmTexto(string $textoEscapado): string
    {
        $bloqueados = [
            'The', 'This', 'These', 'Those', 'When', 'With', 'From', 'After', 'Before', 'More', 'Some', 'Such',
            'Also', 'Here', 'Thus', 'Only', 'Even', 'Into', 'Over', 'Under', 'Between', 'Among', 'During',
            'Without', 'Within', 'About', 'Above', 'Below', 'Since', 'Until', 'While', 'Where', 'Which',
            'Whose', 'Their', 'There', 'Other', 'First', 'Second', 'Third', 'South', 'North', 'Latin',
            'Central', 'United', 'World', 'Health', 'Disease', 'American', 'Chagas', 'Despite', 'Currently',
            'Several', 'However', 'Therefore', 'Moreover', 'Finally', 'Furthermore', 'Introdução', 'Resultados',
            'Continuacao', 'Continuação', 'Methods', 'Results', 'Discussion',
        ];

        // Binômio completo: "Triatoma infestans".
        $textoEscapado = (string) preg_replace_callback(
            '/(?<!<\/em>)\b([A-Z][a-z]{2,})\s([a-z]{3,})\b(?![^<]*<\/em>)/u',
            function (array $m) use ($bloqueados): string {
                if (in_array($m[1], $bloqueados, true)) {
                    return $m[0];
                }

                if (! $this->pareceEpitetoEspecifico($m[2])) {
                    return $m[0];
                }

                return '<em>'.$m[1].' '.$m[2].'</em>';
            },
            $textoEscapado,
        );

        // Abreviação como no HTML do portal: "T. infestans", "R. prolixus".
        return (string) preg_replace_callback(
            '/(?<!<\/em>)\b([A-Z]\.)\s([a-z]{3,})\b(?![^<]*<\/em>)/u',
            function (array $m): string {
                if (! $this->pareceEpitetoEspecifico($m[2])) {
                    return $m[0];
                }

                return '<em>'.$m[1].' '.$m[2].'</em>';
            },
            $textoEscapado,
        );
    }

    private function marcarFigurasETabelas(string $textoEscapado): string
    {
        if (str_contains($textoEscapado, '<strong')) {
            return $textoEscapado;
        }

        return (string) preg_replace(
            '/\b((?:Fig(?:ure|\.)?|Tab(?:le|\.)?)\s*\d+[A-Za-z]?)\b/u',
            '<strong>$1</strong>',
            $textoEscapado,
        );
    }

    private function pareceEpitetoEspecifico(string $epiteto): bool
    {
        if (mb_strlen($epiteto) < 4) {
            return false;
        }

        $ingles = [
            'have', 'has', 'had', 'were', 'was', 'are', 'been', 'being', 'from', 'with', 'that',
            'this', 'these', 'those', 'into', 'over', 'under', 'also', 'only', 'more', 'most',
            'such', 'than', 'then', 'when', 'where', 'which', 'while', 'after', 'before',
        ];

        if (in_array($epiteto, $ingles, true)) {
            return false;
        }

        return preg_match(
            '/^[a-z]{4,}(?:us|um|is|ii|ae|ensis|oides|ans|orum|arum|ata|ina|ella|ica|ana|alis)$/u',
            $epiteto,
        ) === 1
            || preg_match('/^[a-z]{5,}[ai]$/u', $epiteto) === 1;
    }

    /**
     * @param  callable(string): string  $fn
     */
    private function mapearSomenteTexto(string $html, callable $fn): string
    {
        return (string) preg_replace_callback(
            '/(<[^>]+>)|([^<]+)/u',
            function (array $m) use ($fn): string {
                if (($m[1] ?? '') !== '') {
                    return $m[1];
                }

                return $fn($m[2] ?? '');
            },
            $html,
        );
    }

    /**
     * Aplica tipografia só em texto fora de em/strong/sup/a (evita <em><em>…).
     *
     * @param  callable(string): string  $fn
     */
    private function mapearTextoForaDeTipografia(string $html, callable $fn): string
    {
        $resultado = '';
        $profundidade = 0;
        $len = strlen($html);
        $i = 0;

        while ($i < $len) {
            if ($html[$i] === '<') {
                $fecha = strpos($html, '>', $i);

                if ($fecha === false) {
                    $resultado .= substr($html, $i);
                    break;
                }

                $tag = substr($html, $i, $fecha - $i + 1);
                $resultado .= $tag;

                if (preg_match('/^<\/\s*(em|strong|sup|a)\b/i', $tag) === 1) {
                    $profundidade = max(0, $profundidade - 1);
                } elseif (preg_match('/^<\s*(em|strong|sup|a)\b/i', $tag) === 1 && ! str_ends_with($tag, '/>')) {
                    $profundidade++;
                }

                $i = $fecha + 1;

                continue;
            }

            $prox = strpos($html, '<', $i);
            $texto = $prox === false ? substr($html, $i) : substr($html, $i, $prox - $i);
            $i = $prox === false ? $len : $prox;

            $resultado .= $profundidade > 0 ? $texto : $fn($texto);
        }

        return $resultado;
    }

    /**
     * @param  list<array{path: string, url: string, alt?: string}>  $imagens
     */
    private function inserirImagens(string $html, array $imagens): string
    {
        if ($imagens === []) {
            return $html;
        }

        $faltantes = [];

        foreach ($imagens as $imagem) {
            $url = (string) ($imagem['url'] ?? '');

            if ($url === '' || str_contains($html, $url)) {
                continue;
            }

            $faltantes[] = $imagem;
        }

        if ($faltantes === []) {
            return $html;
        }

        $blocosPorIndice = [];

        foreach ($faltantes as $indice => $imagem) {
            $src = e($imagem['url']);
            $alt = e($imagem['alt'] ?? 'figura '.($indice + 1));
            $blocosPorIndice[$indice] = '<p style="text-align:center"><img src="'.$src.'" alt="'.$alt.'"></p>';
        }

        // Tenta ancorar em "Fig. N" / "Figure N".
        foreach ($blocosPorIndice as $indice => $bloco) {
            $n = $indice + 1;
            $padrao = '/(Fig(?:ure|\.)?\s*'.$n.'\b.*?<\/p>)/is';

            if (preg_match($padrao, $html) === 1) {
                $html = (string) preg_replace($padrao, '$1'.$bloco, $html, 1);
                unset($blocosPorIndice[$indice]);
            }
        }

        if ($blocosPorIndice === []) {
            return $html;
        }

        $blocos = implode('', $blocosPorIndice);

        preg_match_all('/<\/p>/i', $html, $m, PREG_OFFSET_CAPTURE);
        $fechamentos = $m[0] ?? [];

        if ($fechamentos === []) {
            return $html.$blocos;
        }

        $indice = min(count($fechamentos) - 1, (int) floor(count($fechamentos) * 0.35));
        $offset = $fechamentos[$indice][1] + strlen($fechamentos[$indice][0]);

        return substr($html, 0, $offset).$blocos.substr($html, $offset);
    }
}
