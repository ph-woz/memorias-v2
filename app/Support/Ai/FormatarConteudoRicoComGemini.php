<?php

namespace App\Support\Ai;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Usa o PDF como referência visual/textual para a IA devolver HTML editorial rico
 * (parágrafos, itálico, negrito, sobrescritos, títulos de seção e figuras).
 */
class FormatarConteudoRicoComGemini
{
    /**
     * @param  list<array{path: string, url: string, alt?: string}>  $imagens
     * @return array{conteudo_html: string, resumo_html: ?string}
     */
    public function formatar(
        string $caminhoPdfPublico,
        string $textoPdf,
        string $model,
        array $imagens = [],
    ): array {
        $apiKey = (string) config('ai.providers.gemini.key');

        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY não configurada.');
        }

        if (! Storage::disk('public')->exists($caminhoPdfPublico)) {
            throw new RuntimeException('PDF não encontrado para formatação rica.');
        }

        $bytes = Storage::disk('public')->get($caminhoPdfPublico);
        $base64 = base64_encode($bytes);
        $url = rtrim((string) config('ai.providers.gemini.url'), '/').'/models/'.$model.':generateContent';

        $prompt = $this->prompt($textoPdf, $imagens);

        /** @var Response $response */
        $response = retry(
            times: 5,
            callback: function () use ($apiKey, $url, $prompt, $base64): Response {
                $response = Http::timeout(240)
                    ->connectTimeout(15)
                    ->withQueryParameters(['key' => $apiKey])
                    ->acceptJson()
                    ->post($url, [
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [
                                    [
                                        'inline_data' => [
                                            'mime_type' => 'application/pdf',
                                            'data' => $base64,
                                        ],
                                    ],
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                            'responseSchema' => $this->schema(),
                            'temperature' => 0,
                            'maxOutputTokens' => 65536,
                        ],
                    ]);

                if (in_array($response->status(), [429, 503], true)) {
                    throw new RuntimeException(
                        'Falha na API Gemini: HTTP '.$response->status().' — '.$response->json('error.message', $response->body())
                    );
                }

                return $response;
            },
            sleepMilliseconds: fn (int $attempt): int => min(25000, 3000 * $attempt),
        );

        if (! $response->successful()) {
            throw new RuntimeException(
                'Falha na API Gemini: HTTP '.$response->status().' — '.$response->json('error.message', $response->body())
            );
        }

        $texto = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $dados = json_decode($texto, true);

        if (! is_array($dados) || trim((string) ($dados['conteudo_html'] ?? '')) === '') {
            throw new RuntimeException('A IA não devolveu HTML rico do PDF.');
        }

        return [
            'conteudo_html' => $this->sanitizarHtml((string) $dados['conteudo_html']),
            'resumo_html' => filled($dados['resumo_html'] ?? null)
                ? $this->sanitizarHtml((string) $dados['resumo_html'])
                : null,
        ];
    }

    /**
     * Tenta o modelo configurado e, em 503/429, modelos alternativos.
     *
     * @param  list<array{path: string, url: string, alt?: string}>  $imagens
     * @return array{conteudo_html: string, resumo_html: ?string}
     */
    public function formatarComFallback(
        string $caminhoPdfPublico,
        string $textoPdf,
        string $model,
        array $imagens = [],
    ): array {
        $candidatos = array_values(array_unique(array_filter([
            $model,
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-3.5-flash-lite',
        ])));

        $ultimo = null;

        foreach ($candidatos as $candidato) {
            try {
                return $this->formatar($caminhoPdfPublico, $textoPdf, $candidato, $imagens);
            } catch (RuntimeException $exception) {
                $ultimo = $exception;

                if (! str_contains($exception->getMessage(), 'HTTP 503')
                    && ! str_contains($exception->getMessage(), 'HTTP 429')) {
                    throw $exception;
                }
            }
        }

        throw $ultimo ?? new RuntimeException('A IA não devolveu HTML rico do PDF.');
    }

    /**
     * @param  list<array{path: string, url: string, alt?: string}>  $imagens
     */
    private function prompt(string $textoPdf, array $imagens): string
    {
        $listaImagens = '';

        foreach ($imagens as $i => $imagem) {
            $n = $i + 1;
            $alt = $imagem['alt'] ?? 'figura '.$n;
            $listaImagens .= "- IMG{$n}: {$imagem['url']} (alt: {$alt})\n";
        }

        if ($listaImagens === '') {
            $listaImagens = "(nenhuma imagem embutida extraída; descreva figuras com <figure><figcaption>…</figcaption></figure> se existirem no PDF, sem inventar src)\n";
        }

        return <<<PROMPT
Você é tipógrafo editorial científico. O PDF anexado é a ÚNICA referência visual e textual.

Missão: devolver HTML rico do CORPO do artigo (após abstract/keywords; antes de REFERENCES),
fiel ao PDF — mesmas palavras — com formatação tipográfica completa.

REGRAS ABSOLUTAS:
1. NÃO parafraseie, NÃO resuma, NÃO traduza, NÃO invente frases.
2. Preserve o texto literal do PDF (incluindo citações numéricas).
3. Estruture com MUITOS <p> curtos (ideal 2–5 frases cada), como os blocos visuais do PDF. Nunca um único bloco/parágrafo gigante.
4. Nomes científicos / espécies em <em>…</em> (ex.: <em>Triatoma infestans</em>).
5. Títulos de seção do PDF em <h2>…</h2>.
6. Citações no texto SOMENTE como <sup>(2)</sup> ou <sup>(3, 4, 5)</sup> — só os números da referência. NUNCA coloque anos, volumes, páginas nem texto bibliográfico dentro do <sup>.
7. Negritos do PDF em <strong>.
8. Figuras/tabelas: use as URLs listadas abaixo com <p style="text-align:center"><img src="URL" alt="…"></p> no ponto do PDF em que aparecem.
9. Não inclua cabeçalho da revista, autores, abstract, key words nem REFERENCES no conteudo_html.
10. resumo_html = abstract literal com a mesma marcação (<em>, <sup>).
11. Prefira dezenas de <p> a poucos <p> longos — legibilidade tipográfica é obrigatória.

Imagens disponíveis (use o src exato):
{$listaImagens}

Texto extraído do PDF (apoio; o PDF visual manda na tipografia):
TEXTO:
PROMPT.Str::limit($textoPdf, 60000, '');
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'conteudo_html' => ['type' => 'STRING'],
                'resumo_html' => ['type' => 'STRING'],
            ],
            'required' => ['conteudo_html', 'resumo_html'],
        ];
    }

    private function sanitizarHtml(string $html): string
    {
        $html = trim($html);
        $html = preg_replace('/^```(?:html)?\s*/i', '', $html) ?? $html;
        $html = preg_replace('/\s*```$/', '', $html) ?? $html;

        // Remove scripts/styles perigosos; mantém tipografia.
        $html = preg_replace('/<(script|style|iframe|object|embed)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/\son\w+="[^"]*"/i', '', $html) ?? $html;

        return trim($html);
    }
}
