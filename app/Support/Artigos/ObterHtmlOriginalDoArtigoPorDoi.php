<?php

namespace App\Support\Artigos;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Resolve o HTML editorial original (SciELO / Memórias) a partir do DOI do artigo.
 */
class ObterHtmlOriginalDoArtigoPorDoi
{
    public function __construct(
        private readonly ExtrairArtigoRicoDoHtmlScielo $extrairScielo = new ExtrairArtigoRicoDoHtmlScielo,
        private readonly ExtrairArtigoRicoDoHtmlMemorias $extrairMemorias = new ExtrairArtigoRicoDoHtmlMemorias,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function obter(?string $doi): ?array
    {
        return $this->obterComUrlsExtras($doi, []);
    }

    /**
     * @param  list<string>  $urlsExtras
     * @return array<string, mixed>|null
     */
    public function obterComUrlsExtras(?string $doi, array $urlsExtras = []): ?array
    {
        $doi = trim((string) $doi);
        $urls = array_values(array_unique(array_filter([
            ...array_values($urlsExtras),
            ...($doi !== '' && str_starts_with($doi, '10.') ? $this->resolverUrls($doi) : []),
        ])));

        $melhor = null;
        $melhorLen = 0;

        foreach ($urls as $url) {
            if (! is_string($url) || $url === '') {
                continue;
            }

            $html = $this->baixar($url);

            if ($html === null || strlen($html) < 1000) {
                continue;
            }

            $extraido = str_contains($url, 'memorias.ioc.fiocruz.br')
                ? $this->extrairMemorias->extrair($html, 'https://memorias.ioc.fiocruz.br')
                : $this->extrairScielo->extrair($html, 'https://www.scielo.br');

            if ($extraido === null) {
                continue;
            }

            $len = mb_strlen(strip_tags((string) ($extraido['conteudo_html'] ?? '')));

            if ($len < 400) {
                continue;
            }

            $extraido['html_origem_url'] = $url;

            // Prefere o HTML publicado com maior corpo editorial (ex.: Memórias > SciELO truncado).
            if ($len > $melhorLen) {
                $melhor = $extraido;
                $melhorLen = $len;
            }
        }

        return $melhor;
    }

    /**
     * @return list<string>
     */
    private function resolverUrls(string $doi): array
    {
        $urls = [];

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->get('https://api.crossref.org/works/'.rawurlencode($doi));

            if ($response->successful()) {
                $message = $response->json('message') ?? [];
                $primary = data_get($message, 'resource.primary.URL');

                if (is_string($primary) && $primary !== '') {
                    $urls[] = $this->forcarHttps($primary);
                }

                foreach ($message['link'] ?? [] as $link) {
                    $url = is_array($link) ? (string) ($link['URL'] ?? '') : '';

                    if ($url === '') {
                        continue;
                    }

                    $url = $this->forcarHttps($url);

                    if (str_contains($url, 'sci_arttext') || str_contains($url, '/j/mioc/')) {
                        $urls[] = $url;
                        $urls[] = preg_replace('/([?&])format=pdf/i', '$1lang=en', $url) ?? $url;
                        $urls[] = preg_replace('/\?.*$/', '?lang=en', $url) ?? $url;
                    }
                }
            }
        } catch (Throwable) {
            // Sem Crossref, não há URL candidata automática.
        }

        return array_values(array_unique(array_filter($urls)));
    }

    private function baixar(string $url): ?string
    {
        try {
            $response = Http::timeout(45)
                ->withHeaders(['User-Agent' => 'MemoriasImporter/1.0'])
                ->withOptions(['verify' => false])
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            return $response->body();
        } catch (Throwable) {
            return null;
        }
    }

    private function forcarHttps(string $url): string
    {
        return preg_replace('/^http:\/\//i', 'https://', $url) ?? $url;
    }
}
