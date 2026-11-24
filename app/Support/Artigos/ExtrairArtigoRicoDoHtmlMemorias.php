<?php

namespace App\Support\Artigos;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Extrai HTML editorial rico da página pública das Memórias
 * (título, autores, instituições, abstract, corpo com em/strong/sup/img, keywords, referências).
 */
class ExtrairArtigoRicoDoHtmlMemorias
{
    /**
     * @return array<string, mixed>|null
     */
    public function extrair(string $html, string $baseUrl = 'https://memorias.ioc.fiocruz.br'): ?array
    {
        $dom = new DOMDocument;
        $interno = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($interno);

        $xpath = new DOMXPath($dom);

        $tituloNode = $xpath->query('//div[contains(@class,"title")]/h1')->item(0);
        $autoresNode = $xpath->query('//div[contains(@class,"authors")]')->item(0);
        $instituicoesNode = $xpath->query('//div[contains(@class,"institutions")]')->item(0);
        $abstractNode = $xpath->query('//*[@id="textabstract"]')->item(0);
        $doiNode = $xpath->query('//span[contains(@class,"doi")]')->item(0);
        $correspondenteNode = $xpath->query('//*[contains(text(),"Corresponding author")]/ancestor::p[1]')->item(0)
            ?? $xpath->query('//p[contains(.,"Corresponding author")]')->item(0);

        $corpoHtml = $this->extrairCorpo($xpath);
        $referencias = $this->extrairReferencias($xpath);
        $keywords = $this->extrairKeywords($xpath);

        if ($tituloNode === null && $corpoHtml === '') {
            return null;
        }

        $autoresHtml = $autoresNode instanceof DOMNode ? $this->innerHtml($autoresNode) : '';
        $autoresHtml = $this->primeiroBlocoHtml($autoresHtml, ['p', 'h6']);

        return [
            'titulo' => $tituloNode?->textContent ? trim(preg_replace('/\s+/u', ' ', $tituloNode->textContent) ?? '') : null,
            'doi' => $this->extrairDoiTexto($doiNode?->textContent ?? ''),
            'resumo' => $abstractNode instanceof DOMNode ? $this->limparHtml($this->innerHtml($abstractNode)) : null,
            'autores_html' => $autoresHtml !== '' ? $this->limparHtml($autoresHtml) : null,
            'autores' => $this->nomesDeAutores($autoresHtml),
            'endereco_instituicoes' => $instituicoesNode instanceof DOMNode
                ? $this->limparHtml($this->primeiroBlocoHtml($this->innerHtml($instituicoesNode), ['p', 'h6']))
                : null,
            'autor_correspondente' => $correspondenteNode instanceof DOMNode
                ? $this->limparHtml($this->outerHtml($correspondenteNode))
                : null,
            'palavras_chave' => $keywords,
            'conteudo_html' => $this->limparHtml($corpoHtml),
            'referencias' => $referencias,
            'imagens' => $this->listarImagens($corpoHtml.' '.($abstractNode ? $this->innerHtml($abstractNode) : ''), $baseUrl),
            'fonte' => 'memorias',
            'base_url' => $baseUrl,
        ];
    }

    private function extrairCorpo(DOMXPath $xpath): string
    {
        $nodes = $xpath->query('//article[contains(@class,"full-article")]//div[contains(@class,"full-content")]');

        if ($nodes === false || $nodes->length === 0) {
            return '';
        }

        // O primeiro full-content costuma ser o abstract; o corpo editorial vem depois.
        $html = '';

        foreach ($nodes as $indice => $node) {
            if (! $node instanceof DOMNode) {
                continue;
            }

            if ($indice === 0 && $xpath->query('.//*[@id="textabstract"]', $node)->length > 0) {
                continue;
            }

            $html .= $this->innerHtml($node);
        }

        // Remove bloco de keywords se veio embutido.
        $html = preg_replace('/<div[^>]*class="keywords"[^>]*>.*?<\/div>/is', '', $html) ?? $html;

        return trim($html);
    }

    /**
     * @return list<string>
     */
    private function extrairReferencias(DOMXPath $xpath): array
    {
        $nodes = $xpath->query('//aside[@id="references"]//div[contains(@class,"reference")]');
        $refs = [];

        if ($nodes === false) {
            return [];
        }

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $numero = '';
            $numberNode = null;

            foreach ($node->getElementsByTagName('span') as $span) {
                if (str_contains($span->getAttribute('class'), 'number')) {
                    $numberNode = $span;
                    $numero = trim($span->textContent);
                    break;
                }
            }

            $clone = $node->cloneNode(true);

            if ($clone instanceof DOMElement && $numberNode instanceof DOMNode) {
                foreach ($clone->getElementsByTagName('span') as $span) {
                    if (str_contains($span->getAttribute('class'), 'number')) {
                        $span->parentNode?->removeChild($span);
                        break;
                    }
                }

                foreach ($clone->getElementsByTagName('div') as $div) {
                    if (str_contains($div->getAttribute('class'), 'links-reference')) {
                        $div->parentNode?->removeChild($div);
                    }
                }
            }

            $texto = trim(html_entity_decode(strip_tags($this->innerHtml($clone)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

            if ($texto === '') {
                continue;
            }

            $refs[] = trim($numero.' '.$texto);
        }

        return $refs;
    }

    /**
     * @return list<string>
     */
    private function extrairKeywords(DOMXPath $xpath): array
    {
        $nodes = $xpath->query('//div[contains(@class,"keywords")]//a');
        $keywords = [];

        if ($nodes === false) {
            return [];
        }

        foreach ($nodes as $node) {
            $texto = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');

            if ($texto !== '') {
                $keywords[] = $texto;
            }
        }

        return $keywords;
    }

    /**
     * @return list<string>
     */
    private function nomesDeAutores(string $autoresHtml): array
    {
        $texto = html_entity_decode(strip_tags($autoresHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\d+/u', '', $texto) ?? $texto;
        $texto = str_replace(['+', '/'], ' ', $texto);
        $partes = preg_split('/\s*,\s*/u', $texto) ?: [];

        return array_values(array_filter(array_map(
            static fn (string $nome): string => trim(preg_replace('/\s+/u', ' ', $nome) ?? ''),
            $partes,
        ), static fn (string $nome): bool => mb_strlen($nome) >= 5));
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
            if (str_contains($src, 'orcid')) {
                continue;
            }

            $abs = str_starts_with($src, 'http')
                ? $src
                : rtrim($baseUrl, '/').'/'.ltrim($src, '/');

            $imagens[] = ['src' => $src, 'abs' => $abs];
        }

        return $imagens;
    }

    private function extrairDoiTexto(string $texto): ?string
    {
        if (preg_match('/10\.\d{4,}\/[^\s<]+/u', $texto, $m) === 1) {
            return rtrim($m[0], '.;,');
        }

        return null;
    }

    private function primeiroBlocoHtml(string $html, array $tags): string
    {
        foreach ($tags as $tag) {
            if (preg_match('/<'.$tag.'\b[^>]*>.*?<\/'.$tag.'>/is', $html, $m) === 1) {
                return $m[0];
            }
        }

        return trim($html);
    }

    private function limparHtml(string $html): string
    {
        $html = preg_replace('/\s+/u', ' ', $html) ?? $html;
        $html = preg_replace('/>\s+</u', '><', $html) ?? $html;
        // Normaliza citações para <sup>(n)</sup> consumível pelo VinculaCitacoesNoConteudo.
        $html = preg_replace(
            '/<sup>\(?(?:<a[^>]*class="click-reference"[^>]*>)?(\d+)(?:<\/a>)?(?:\s*,\s*(?:<a[^>]*class="click-reference"[^>]*>)?(\d+)(?:<\/a>)?)*\)?<\/sup>(?:<\/sup>)?/i',
            '<sup>($1)</sup>',
            $html,
        ) ?? $html;
        $html = preg_replace_callback(
            '/<sup>\((?:<a[^>]*>(\d+)<\/a>\s*,\s*)*<a[^>]*>(\d+)<\/a>\)<\/sup>/i',
            static function (array $m): string {
                return $m[0];
            },
            $html,
        ) ?? $html;

        // Reescreve citações click-reference em <sup>1, 2</sup>.
        $html = preg_replace_callback(
            '/<sup>\((.*?)<\/sup>(?:<\/sup>)?/is',
            static function (array $m): string {
                preg_match_all('/\d+/', $m[1], $nums);

                if ($nums[0] === []) {
                    return $m[0];
                }

                return '<sup>('.implode(', ', array_unique($nums[0])).')</sup>';
            },
            $html,
        ) ?? $html;

        return trim($html);
    }

    private function innerHtml(DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument?->saveHTML($child) ?? '';
        }

        return $html;
    }

    private function outerHtml(DOMNode $node): string
    {
        return $node->ownerDocument?->saveHTML($node) ?? '';
    }
}
