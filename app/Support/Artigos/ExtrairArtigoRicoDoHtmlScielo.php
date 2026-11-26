<?php

namespace App\Support\Artigos;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Extrai o HTML editorial publicado no SciELO (fonte HTML canônica das Memórias),
 * preservando parágrafos, itálicos, sobrescritos, títulos e imagens.
 */
class ExtrairArtigoRicoDoHtmlScielo
{
    /**
     * @return array<string, mixed>|null
     */
    public function extrair(string $html, string $baseUrl = 'https://www.scielo.br'): ?array
    {
        $dom = new DOMDocument;
        $interno = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($interno);

        $xpath = new DOMXPath($dom);

        $tituloNode = $xpath->query('//*[@class="article-title"]')->item(0)
            ?? $xpath->query('//h1[contains(@class,"article")]')->item(0)
            ?? $xpath->query('//meta[@name="citation_title"]')->item(0);

        $titulo = null;
        if ($tituloNode instanceof DOMElement && strtolower($tituloNode->nodeName) === 'meta') {
            $titulo = trim($tituloNode->getAttribute('content'));
        } elseif ($tituloNode !== null) {
            $titulo = trim(preg_replace('/\s+/u', ' ', $tituloNode->textContent) ?? '');
        }

        $abstractHtml = $this->htmlDaSecao($xpath, 'Abstract');
        $corpoHtml = $this->htmlDaSecaoTexto($xpath);
        $ackHtml = $this->htmlDaSecao($xpath, 'ACKNOWLEDGEMENTS');
        $contribuicaoHtml = $this->htmlDaSecao($xpath, 'Author contributions');
        $referencias = $this->extrairReferencias($xpath);
        $keywords = $this->extrairKeywords($xpath, $abstractHtml);

        if (($titulo === null || $titulo === '') && $corpoHtml === '') {
            return null;
        }

        if ($ackHtml !== '') {
            $ackHtml = $this->removerTituloInicialDaSecao($ackHtml, 'ACKNOWLEDGEMENTS?');
            $corpoHtml .= '<h2><strong>ACKNOWLEDGEMENTS</strong></h2>'.$ackHtml;
        }

        if ($contribuicaoHtml !== '') {
            $contribuicaoHtml = $this->removerTituloInicialDaSecao(
                $contribuicaoHtml,
                'Author(?:s)?[\'’]?\s*contributions?',
            );
            $corpoHtml .= '<h2><strong>AUTHORS’ CONTRIBUTION</strong></h2>'.$contribuicaoHtml;
        }

        $corpoHtml = $this->normalizarTipografia($corpoHtml);
        $resumoHtml = $abstractHtml !== '' ? trim($this->normalizarTipografia($abstractHtml)) : '';
        $resumoHtml = $resumoHtml !== '' ? $resumoHtml : null;

        return [
            'titulo' => $titulo !== '' ? $titulo : null,
            'doi' => $this->extrairDoi($xpath, $html),
            'resumo' => $resumoHtml,
            'palavras_chave' => $keywords,
            'conteudo_html' => $corpoHtml,
            'referencias' => $referencias,
            'imagens' => $this->listarImagens($corpoHtml, $baseUrl),
            'fonte' => 'scielo',
            'base_url' => $baseUrl,
        ];
    }

    private function htmlDaSecao(DOMXPath $xpath, string $anchor): string
    {
        $nodes = $xpath->query('//*[@data-anchor="'.$anchor.'"]');

        if ($nodes === false || $nodes->length === 0) {
            return '';
        }

        return $this->innerHtml($nodes->item(0));
    }

    private function htmlDaSecaoTexto(DOMXPath $xpath): string
    {
        $nodes = $xpath->query('//*[@data-anchor="Text"]');

        if ($nodes === false || $nodes->length === 0) {
            return '';
        }

        $melhor = '';
        $melhorLen = 0;

        foreach ($nodes as $node) {
            if (! $node instanceof DOMNode) {
                continue;
            }

            $texto = trim(preg_replace('/\s+/u', ' ', $node->textContent ?? '') ?? '');

            if (preg_match('/^REVIEWER\s*#/i', $texto) === 1) {
                continue;
            }

            if (mb_strlen($texto) > $melhorLen) {
                $melhorLen = mb_strlen($texto);
                $melhor = $this->innerHtml($node);
            }
        }

        return $melhor;
    }

    /**
     * @return list<string>
     */
    private function extrairReferencias(DOMXPath $xpath): array
    {
        $html = $this->htmlDaSecao($xpath, 'REFERENCES');

        if ($html === '') {
            return [];
        }

        $refs = [];

        if (preg_match_all('/<(?:p|li)\b[^>]*>(.*?)<\/(?:p|li)>/is', $html, $m) > 0) {
            foreach ($m[1] as $item) {
                $texto = (new NormalizarTextoReferencia)->normalizar($item);

                if ($texto === '' || preg_match('/^REFERENCES$/iu', $texto) === 1) {
                    continue;
                }

                $refs[] = $texto;
            }
        }

        return $refs;
    }

    /**
     * @return list<string>
     */
    private function extrairKeywords(DOMXPath $xpath, string $abstractHtml): array
    {
        $nodes = $xpath->query('//*[contains(@class,"kwd") or contains(@class,"keyword")]');
        $keywords = [];

        if ($nodes !== false) {
            foreach ($nodes as $node) {
                $texto = trim(preg_replace('/\s+/u', ' ', $node->textContent ?? '') ?? '');
                if ($texto !== '' && ! preg_match('/^key words?:/iu', $texto)) {
                    $keywords[] = $texto;
                }
            }
        }

        if ($keywords === [] && preg_match('/Key words?:\s*(.+)/iu', strip_tags($abstractHtml), $m) === 1) {
            $partes = preg_split('/\s*[;,-]\s*/u', $m[1]) ?: [];
            $keywords = array_values(array_filter(array_map('trim', $partes)));
        }

        return array_values(array_unique($keywords));
    }

    private function extrairDoi(DOMXPath $xpath, string $html): ?string
    {
        $meta = $xpath->query('//meta[@name="citation_doi"]')->item(0);
        if ($meta instanceof DOMElement) {
            $doi = trim($meta->getAttribute('content'));
            $doi = preg_replace('/^doi:\s*/iu', '', $doi) ?? $doi;

            if ($doi !== '') {
                return $doi;
            }
        }

        if (preg_match('/10\.\d{4,}\/[^\s<"\']+/u', $html, $m) === 1) {
            return rtrim($m[0], '.;,');
        }

        return null;
    }

    /**
     * @return list<array{src: string, abs: string}>
     */
    private function listarImagens(string $html, string $baseUrl): array
    {
        if (preg_match_all('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $matches) === 0) {
            return [];
        }

        $imagens = [];

        foreach ($matches[1] as $src) {
            $abs = str_starts_with($src, 'http')
                ? $src
                : rtrim($baseUrl, '/').'/'.ltrim($src, '/');

            $imagens[] = ['src' => $src, 'abs' => $abs];
        }

        return $imagens;
    }

    /**
     * Remove o título já presente no HTML da seção SciELO (ex.: <h2 class="h5">ACKNOWLEDGEMENTS</h2>)
     * antes de anexarmos o <h2> canônico — evita subtítulo duplicado.
     */
    private function removerTituloInicialDaSecao(string $html, string $tituloRegex): string
    {
        $padraoBloco = '/^\s*<(?:h[1-6]|p)\b[^>]*>\s*(?:<(?:strong|b|em|i|span)\b[^>]*>\s*)*'
            .$tituloRegex
            .'\s*(?:<\/(?:strong|b|em|i|span)>\s*)*<\/(?:h[1-6]|p)>\s*/iu';

        $anterior = null;

        while ($anterior !== $html) {
            $anterior = $html;
            $html = preg_replace($padraoBloco, '', $html) ?? $html;
        }

        // Título colado no início do primeiro parágrafo: "ACKNOWLEDGEMENTS To Dr..."
        $html = preg_replace(
            '/^\s*(<p\b[^>]*>)\s*(?:<(?:strong|b)\b[^>]*>\s*)*'.$tituloRegex.'\s*(?:<\/(?:strong|b)>\s*)*/iu',
            '$1',
            $html,
        ) ?? $html;

        $html = preg_replace('/^\s*<p\b[^>]*>\s*<\/p>\s*/iu', '', $html) ?? $html;

        return trim($html);
    }

    private function normalizarTipografia(string $html): string
    {
        // SciELO usa <i>/<b>; o editor TipTap das Memórias trabalha com <em>/<strong>.
        $html = preg_replace('/<i\b([^>]*)>/i', '<em$1>', $html) ?? $html;
        $html = preg_replace('/<\/i>/i', '</em>', $html) ?? $html;
        $html = preg_replace('/<b\b([^>]*)>/i', '<strong$1>', $html) ?? $html;
        $html = preg_replace('/<\/b>/i', '</strong>', $html) ?? $html;

        // Citações SciELO: <sup><a class="xref">1</a></sup> → <sup>(1)</sup> (sem anos/páginas).
        $html = (new NormalizarCitacoesNoHtml)->normalizar($html);

        // Remove âncoras vazias e scripts/estilos residuais.
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/<a\b[^>]*>\s*<\/a>/i', '', $html) ?? $html;
        $html = preg_replace('/\s+/u', ' ', $html) ?? $html;
        $html = preg_replace('/>\s+</u', '><', $html) ?? $html;

        return trim($html);
    }

    private function innerHtml(?DOMNode $node): string
    {
        if ($node === null) {
            return '';
        }

        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument?->saveHTML($child) ?? '';
        }

        return $html;
    }
}
