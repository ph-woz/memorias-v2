<?php

namespace App\Support\Artigos;

/**
 * Enriquece HTML literal do PDF com tipografia científica básica
 * (parágrafos, itálicos de espécies, sobrescritos de citação, imagens).
 */
class EnriquecerHtmlLiteralDoPdf
{
    /**
     * @param  list<array{path: string, url: string, alt?: string}>  $imagens
     */
    public function enriquecer(string $html, array $imagens = []): string
    {
        $html = trim($html);

        if ($html === '') {
            return $html;
        }

        if (! str_contains($html, '<')) {
            $html = $this->textoParaParagrafos($html);
        }

        $html = $this->garantirParagrafos($html);
        $html = $this->repartirParagrafosLongos($html);
        $html = $this->mapearConteudoDeParagrafos($html);
        $html = $this->inserirImagens($html, $imagens);

        return $html;
    }

    /**
     * Mesmo com HTML vindo da IA, evita "parede de texto" repartindo <p> longos.
     */
    public function repartirParagrafosLongos(string $html, int $limite = 700): string
    {
        return (string) preg_replace_callback(
            '/<p\b([^>]*)>(.*?)<\/p>/is',
            function (array $m) use ($limite): string {
                // Não destrói tipografia rica já marcada pela IA (<em>, <sup>, <img>…).
                if (str_contains($m[2], '<')) {
                    return $m[0];
                }

                $texto = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);

                if (mb_strlen($texto) <= $limite) {
                    return $m[0];
                }

                $html = '';

                foreach ($this->partirBlocoLongo($texto) as $parte) {
                    $html .= '<p'.$m[1].'>'.e($parte).'</p>';
                }

                return $html;
            },
            $html,
        );
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
        if (preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $html, $m) === 1) {
            $interno = html_entity_decode(strip_tags($m[1][0]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $interno = trim(preg_replace('/\s+/u', ' ', $interno) ?? $interno);

            if (mb_strlen($interno) > 900) {
                $html = '';

                foreach ($this->partirBlocoLongo($interno) as $parte) {
                    $html .= '<p>'.e($parte).'</p>';
                }
            }
        }

        return $html;
    }

    private function mapearConteudoDeParagrafos(string $html): string
    {
        return (string) preg_replace_callback(
            '/<p\b([^>]*)>(.*?)<\/p>/is',
            function (array $m): string {
                $attrs = $m[1];
                $interno = $m[2];

                if (str_contains($interno, '<img') || str_contains($interno, '<h2')) {
                    return $m[0];
                }

                $texto = html_entity_decode(strip_tags($interno), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);

                if ($texto === '') {
                    return $m[0];
                }

                if ($this->pareceTituloSecao($texto)) {
                    return '<h2>'.e($texto).'</h2>';
                }

                $rico = e($texto);
                $rico = $this->marcarCitacoesEmTexto($rico);
                $rico = $this->marcarEspeciesEmTexto($rico);

                return '<p'.$attrs.'>'.$rico.'</p>';
            },
            $html,
        );
    }

    /**
     * @return list<string>
     */
    private function partirBlocoLongo(string $texto): array
    {
        if (mb_strlen($texto) <= 650) {
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

            if (mb_strlen($candidato) > 600 && $atual !== '') {
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
        if (str_ends_with($texto, '.') || mb_strlen($texto) > 80) {
            return false;
        }

        if (preg_match('/^(Introduction|Methods|Results|Discussion|Conclusions?|Background|Objectives?|Findings|Main conclusions?)\b/iu', $texto) === 1) {
            return true;
        }

        return str_word_count($texto) <= 8
            && substr_count($texto, ',') === 0
            && preg_match('/^[A-Z][A-Za-z0-9 \-]+$/', $texto) === 1
            && preg_match('/\b(the|and|of|in|to|for|with|from|that|this|are|was|were)\b/i', $texto) !== 1;
    }

    private function marcarCitacoesEmTexto(string $textoEscapado): string
    {
        return (string) preg_replace(
            '/\((\d+(?:\s*,\s*\d+)*)\)/u',
            '<sup>($1)</sup>',
            $textoEscapado,
        );
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

        return (string) preg_replace_callback(
            '/\b([A-Z][a-z]{2,})\s([a-z]{3,})\b/u',
            function (array $m) use ($bloqueados): string {
                if (in_array($m[1], $bloqueados, true)) {
                    return $m[0];
                }

                // Só epítetos com cara de latim científico (evita "Resultados completos").
                if (! $this->pareceEpitetoEspecifico($m[2])) {
                    return $m[0];
                }

                return '<em>'.$m[1].' '.$m[2].'</em>';
            },
            $textoEscapado,
        );
    }

    private function pareceEpitetoEspecifico(string $epiteto): bool
    {
        return preg_match(
            '/^[a-z]{3,}(?:us|a|um|is|e|i|ii|ae|ensis|oides|ans|orum|arum)$/u',
            $epiteto,
        ) === 1;
    }

    /**
     * @param  list<array{path: string, url: string, alt?: string}>  $imagens
     */
    public function injetarImagensSeAusentes(string $html, array $imagens): string
    {
        return $this->inserirImagens($html, $imagens);
    }

    /**
     * @param  list<array{path: string, url: string, alt?: string}>  $imagens
     */
    private function inserirImagens(string $html, array $imagens): string
    {
        if ($imagens === [] || str_contains($html, '<img')) {
            return $html;
        }

        $blocos = '';

        foreach ($imagens as $imagem) {
            $src = e($imagem['url']);
            $alt = e($imagem['alt'] ?? 'figura');
            $blocos .= '<p style="text-align:center"><img src="'.$src.'" alt="'.$alt.'"></p>';
        }

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
