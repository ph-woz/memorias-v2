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
        // Bloco editorial completo (#author-reference): Financial support → ORCID → Accepted.
        $correspondenteNode = $xpath->query(
            '//div[contains(concat(" ", normalize-space(@class), " "), " author-reference ")]/p[1]'
        )->item(0)
            ?? $xpath->query('//p[contains(.,"Financial support") and contains(.,"Corresponding author")]')->item(0)
            ?? $xpath->query('//p[contains(.,"Corresponding author") and (contains(.,"Received") or contains(.,"Accepted") or contains(.,"orcid.org"))]')->item(0)
            ?? $xpath->query('//p[contains(.,"Corresponding author")]')->item(0);

        $corpoHtml = $this->extrairCorpo($xpath);
        $referencias = $this->extrairReferencias($xpath);
        $keywords = $this->extrairKeywords($xpath);
        $pdfDownload = $this->extrairUrlPdf($xpath, $baseUrl);

        if ($tituloNode === null && $corpoHtml === '' && $referencias === []) {
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
            'pdf_url' => $pdfDownload,
            'corpo_somente_pdf' => $corpoHtml === '' && $pdfDownload !== null,
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
        // Artigos recentes do portal frequentemente não publicam o corpo em HTML
        // (só abstract + referências + PDF) — não tratar referências como corpo.
        $html = '';

        foreach ($nodes as $indice => $node) {
            if (! $node instanceof DOMNode) {
                continue;
            }

            if ($xpath->query('.//*[@id="textabstract"]', $node)->length > 0) {
                continue;
            }

            if ($this->blocoESoReferencias($xpath, $node)) {
                continue;
            }

            $html .= $this->innerHtml($node);
        }

        // Remove bloco de keywords/referências se veio embutido.
        $html = preg_replace('/<div[^>]*class="keywords"[^>]*>.*?<\/div>/is', '', $html) ?? $html;
        $html = preg_replace('/<div[^>]*class="references"[^>]*>.*?<\/div>/is', '', $html) ?? $html;

        return trim($html);
    }

    private function blocoESoReferencias(DOMXPath $xpath, DOMNode $node): bool
    {
        $refs = $xpath->query('.//div[contains(concat(" ", normalize-space(@class), " "), " reference ")]', $node);
        $paras = $xpath->query('.//p', $node);
        $nRefs = $refs === false ? 0 : $refs->length;
        $nParas = $paras === false ? 0 : $paras->length;

        if ($nRefs >= 3 && $nParas === 0) {
            return true;
        }

        $texto = trim(preg_replace('/\s+/u', ' ', $node->textContent ?? '') ?? '');

        return $nRefs > 0 && preg_match('/^REFERENCES\b/iu', $texto) === 1 && $nParas === 0;
    }

    private function extrairUrlPdf(DOMXPath $xpath, string $baseUrl): ?string
    {
        $nodes = $xpath->query('//a[contains(@href,"task=artigo.download")]');

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $link = $nodes->item(0);
        $href = $link instanceof DOMElement ? trim($link->getAttribute('href')) : '';

        if ($href === '') {
            return null;
        }

        if (str_starts_with($href, 'http')) {
            return $href;
        }

        return rtrim($baseUrl, '/').'/'.ltrim($href, '/');
    }

    /**
     * @return list<string>
     */
    private function extrairReferencias(DOMXPath $xpath): array
    {
        // Classe exata "reference" — evita casar "references" / "links-reference".
        $nodes = $xpath->query('//aside[@id="references"]//div[contains(concat(" ", normalize-space(@class), " "), " reference ")]');
        $refs = [];

        if ($nodes === false) {
            return [];
        }

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $numberNode = null;

            foreach ($node->getElementsByTagName('span') as $span) {
                if (str_contains($span->getAttribute('class'), 'number')) {
                    $numberNode = $span;
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

            $texto = (new NormalizarTextoReferencia)->normalizar($this->innerHtml($clone));

            if ($texto === '' || preg_match('/^REFERENCES$/iu', $texto) === 1) {
                continue;
            }

            $refs[] = $texto;
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
     * @return list<array{nome: string, marcadores: ?string}>
     */
    private function nomesDeAutores(string $autoresHtml): array
    {
        if (preg_match_all(
            '/([^,<]+?)(?:\s*<sup>\s*([^<]*?)\s*<\/sup>)?\s*(?:,|$)/iu',
            $autoresHtml,
            $matches,
            PREG_SET_ORDER,
        ) > 0) {
            $autores = [];

            foreach ($matches as $match) {
                $nome = trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $nome = trim(preg_replace('/\s+/u', ' ', $nome) ?? $nome);
                $marcadores = isset($match[2]) ? preg_replace('/\s+/u', '', $match[2]) : '';

                if (mb_strlen($nome) < 5) {
                    continue;
                }

                $autores[] = [
                    'nome' => $nome,
                    'marcadores' => $marcadores !== '' ? $marcadores : null,
                ];
            }

            if ($autores !== []) {
                return $autores;
            }
        }

        $texto = html_entity_decode(strip_tags($autoresHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\d+/u', '', $texto) ?? $texto;
        $texto = str_replace(['+', '/'], ' ', $texto);
        $partes = preg_split('/\s*,\s*/u', $texto) ?: [];

        return array_values(array_filter(array_map(
            static function (string $nome): array {
                $nome = trim(preg_replace('/\s+/u', ' ', $nome) ?? '');

                return [
                    'nome' => $nome,
                    'marcadores' => null,
                ];
            },
            $partes,
        ), static fn (array $autor): bool => mb_strlen($autor['nome']) >= 5));
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

        // Citações: só números de referência (nunca anos/páginas colados no <sup>).
        return trim((new NormalizarCitacoesNoHtml)->normalizar($html));
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
