<?php

namespace App\Support\Ai;

use App\Support\Artigos\MontarHtmlTabelaDeTextoPdf;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Monta a TABLE I (landscape) como HTML fiel usando um modelo Gemini mais capaz,
 * a partir do texto das páginas de tabela + PDF.
 */
class FormatarTabelaPdfComGemini
{
    /**
     * @param  list<string>  $textosPaginasTabela
     */
    public function montar(
        string $caminhoPdfPublico,
        array $textosPaginasTabela,
        string $model = 'gemini-2.5-flash',
    ): string {
        if ($textosPaginasTabela === []) {
            return '';
        }

        $apiKey = (string) config('ai.providers.gemini.key');

        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY não configurada.');
        }

        $candidatos = array_values(array_unique(array_filter([
            $model,
            'gemini-2.5-flash',
            'gemini-2.5-pro',
            'gemini-2.0-flash',
        ])));

        $ultimo = null;

        foreach ($candidatos as $candidato) {
            try {
                return $this->gerar($caminhoPdfPublico, $textosPaginasTabela, $candidato);
            } catch (RuntimeException $exception) {
                $ultimo = $exception;

                if (! str_contains($exception->getMessage(), 'HTTP 503')
                    && ! str_contains($exception->getMessage(), 'HTTP 429')) {
                    throw $exception;
                }
            }
        }

        throw $ultimo ?? new RuntimeException('A IA não devolveu a tabela HTML do PDF.');
    }

    /**
     * @param  list<string>  $textosPaginasTabela
     */
    public function montarComFallbackLocal(
        string $caminhoPdfPublico,
        array $textosPaginasTabela,
        string $model = 'gemini-2.5-flash',
    ): string {
        try {
            $html = $this->montar($caminhoPdfPublico, $textosPaginasTabela, $model);

            if ($html !== '' && ! $this->tabelaPareceCorrompida($html)) {
                return $html;
            }
        } catch (Throwable $exception) {
            Log::warning('Gemini não montou a TABLE I; usando parser local.', [
                'exception' => $exception->getMessage(),
            ]);
        }

        return (new MontarHtmlTabelaDeTextoPdf)->dePaginas($textosPaginasTabela);
    }

    public function tabelaPareceCorrompida(string $html): bool
    {
        // Marcador de DNA / continuação na coluna Genus.
        if (preg_match('/<tr[^>]*>\s*<td[^>]*>\s*(?:<em>)?\s*(?:Cytb|COI|COII|18S|28S|16S|ITS)/i', $html) === 1) {
            return true;
        }

        if (preg_match('/<tr[^>]*>\s*<td[^>]*>\s*(?:<em>)?\s*and\s*(?:<\/em>)?\s*<\/td>/i', $html) === 1) {
            return true;
        }

        return false;
    }

    /**
     * @param  list<string>  $textosPaginasTabela
     */
    private function gerar(string $caminhoPdfPublico, array $textosPaginasTabela, string $model): string
    {
        $apiKey = (string) config('ai.providers.gemini.key');
        $url = rtrim((string) config('ai.providers.gemini.url'), '/').'/models/'.$model.':generateContent';

        $partes = [];

        if (Storage::disk('public')->exists($caminhoPdfPublico)) {
            $partes[] = [
                'inline_data' => [
                    'mime_type' => 'application/pdf',
                    'data' => base64_encode((string) Storage::disk('public')->get($caminhoPdfPublico)),
                ],
            ];
        }

        $partes[] = ['text' => $this->prompt($textosPaginasTabela)];

        /** @var Response $response */
        $response = retry(
            times: 4,
            callback: function () use ($apiKey, $url, $partes): Response {
                $response = Http::timeout(240)
                    ->connectTimeout(15)
                    ->withQueryParameters(['key' => $apiKey])
                    ->acceptJson()
                    ->post($url, [
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => $partes,
                            ],
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                            'responseSchema' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'tabela_html' => ['type' => 'STRING'],
                                ],
                                'required' => ['tabela_html'],
                            ],
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
            sleepMilliseconds: fn (int $attempt): int => min(20000, 2500 * $attempt),
        );

        if (! $response->successful()) {
            throw new RuntimeException(
                'Falha na API Gemini: HTTP '.$response->status().' — '.$response->json('error.message', $response->body())
            );
        }

        $texto = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $dados = json_decode($texto, true);
        $html = trim((string) ($dados['tabela_html'] ?? ''));
        $html = preg_replace('/^```(?:html)?\s*/i', '', $html) ?? $html;
        $html = trim(preg_replace('/\s*```$/', '', $html) ?? $html);

        if ($html === '' || preg_match('/<table\b/i', $html) !== 1) {
            throw new RuntimeException('A IA não devolveu <table> HTML da TABLE I.');
        }

        if ($this->tabelaPareceCorrompida($html)) {
            throw new RuntimeException('A tabela HTML da IA veio com colunas desalinhadas.');
        }

        if (! str_contains($html, 'tabela-pdf-scroll')) {
            $html = '<div class="tabela-pdf-scroll">'.$html.'</div>';
        }

        return $html;
    }

    /**
     * @param  list<string>  $textosPaginasTabela
     */
    private function prompt(array $textosPaginasTabela): string
    {
        $blocos = '';
        foreach ($textosPaginasTabela as $i => $texto) {
            $n = $i + 1;
            $blocos .= "--- PAGINA_TABELA_{$n} ---\n".trim($texto)."\n\n";
        }

        return <<<PROMPT
Você é tipógrafo editorial científico. Monte a TABLE I do PDF (páginas landscape/rotacionadas)
como HTML <table> LITERAL e fiel.

COLUNAS OBRIGATÓRIAS (nessa ordem):
Genus | Species | Author | Year | Type locality | Distribution | Collection (Type) | Ecotope | DNA sequence

REGRAS ABSOLUTAS:
1. NÃO invente linhas/espécies. Use só o que está no PDF / texto extraído.
2. Genus só com nomes de Triatominae (ex.: Rhodnius, Panstrongylus, Triatoma). NUNCA coloque Cytb, COI, 18S, 28S, 16S, ITS na coluna Genus — isso é DNA sequence (última coluna).
3. Quando o gênero se repete nas linhas seguintes do PDF, repita o Genus em cada <tr>.
4. Species em <em>…</em>; Genus em <em>…</em>. Asteriscos de nota (** ou *) ficam fora do <em> quando forem nota.
5. Ecotope pode ter várias linhas no PDF (ex.: "Attalea butyracea and Oenocarpus bataua") — junte numa célula. A palavra "and" NÃO é Species.
6. DNA sequence: Cytb, COI, 18S, 28S, 16S, ITS-1, ITS-2, "-" ou combinações. Sempre na ÚLTIMA coluna.
7. Devolva só o HTML da tabela (caption TABLE I + thead + tbody). Sem prosa ao redor.
8. Preserve Year como 4 dígitos e Type locality com o número entre parênteses do mapa.

Texto OCR/extraído das páginas de tabela (pode ter quebras; o PDF visual manda):
{$blocos}
PROMPT;
    }
}
