<?php

namespace App\Support\Ai;

use App\Ai\Agents\ExtrairArtigoDoPdf;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class ExtrairArtigoComGemini
{
    /**
     * Extrai apenas metadados e marcadores literais (sem reescrever o corpo).
     *
     * @return array<string, mixed>
     */
    public function extrairMetadados(string $textoPdf, string $model): array
    {
        return $this->gerar(
            $model,
            $this->promptMetadados($textoPdf),
            $this->schemaMetadados(),
        );
    }

    /**
     * @deprecated Use extrairMetadados().
     *
     * @return array<string, mixed>
     */
    public function extrair(string $prompt, string $model): array
    {
        $texto = $this->extrairBlocoTexto($prompt);

        return $this->extrairMetadados($texto, $model);
    }

    private function extrairBlocoTexto(string $prompt): string
    {
        if (preg_match('/TEXTO DO PDF:\s*(.*)$/s', $prompt, $matches) === 1) {
            return trim($matches[1]);
        }

        return trim($prompt);
    }

    private function promptMetadados(string $texto): string
    {
        return (new ExtrairArtigoDoPdf)->instructions()
            ."\n\nTarefa: devolva somente metadados e marcadores literais."
            ."\nNão devolva o corpo do artigo."
            ."\nNão resuma o resumo/abstract."
            ."\nCopie titulo, doi, autores, resumo e marcadores exatamente como no texto."
            ."\n\nTEXTO DO PDF:\n".Str::limit($texto, 80000, '');
    }

    /**
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function gerar(string $model, string $prompt, array $schema): array
    {
        $apiKey = (string) config('ai.providers.gemini.key');

        if ($apiKey === '') {
            throw new RuntimeException('GEMINI_API_KEY não configurada.');
        }

        $url = rtrim((string) config('ai.providers.gemini.url'), '/').'/models/'.$model.':generateContent';

        /** @var Response $response */
        $response = retry(
            times: 6,
            callback: function () use ($apiKey, $url, $prompt, $schema): Response {
                $response = Http::timeout(180)
                    ->withQueryParameters(['key' => $apiKey])
                    ->acceptJson()
                    ->post($url, [
                        'contents' => [
                            [
                                'role' => 'user',
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                            'responseSchema' => $schema,
                            'temperature' => 0,
                        ],
                    ]);

                if (in_array($response->status(), [429, 503], true)) {
                    throw new RuntimeException(
                        'Falha na API Gemini: HTTP '.$response->status().' — '.$response->json('error.message', $response->body())
                    );
                }

                return $response;
            },
            sleepMilliseconds: fn (int $attempt): int => min(15000, 2000 * $attempt),
        );

        if (! $response->successful()) {
            throw new RuntimeException(
                'Falha na API Gemini: HTTP '.$response->status().' — '.$response->json('error.message', $response->body())
            );
        }

        $texto = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        $dados = json_decode($texto, true);

        if (! is_array($dados)) {
            throw new RuntimeException('A API Gemini não retornou JSON estruturado válido.');
        }

        return $dados;
    }

    /**
     * @return array<string, mixed>
     */
    private function schemaMetadados(): array
    {
        $string = ['type' => 'STRING'];
        $stringArray = [
            'type' => 'ARRAY',
            'items' => ['type' => 'STRING'],
        ];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'titulo' => $string,
                'slug' => $string,
                'doi' => $string,
                'resumo' => $string,
                'autores' => $stringArray,
                'endereco_instituicoes' => $string,
                'autor_correspondente' => $string,
                'palavras_chave' => $stringArray,
                'areas_tematicas' => $stringArray,
                'seo_titulo' => $string,
                'seo_descricao' => $string,
                'seo_palavras_chave' => $string,
                'documento_titulo' => $string,
                'marcador_inicio_corpo' => $string,
                'marcador_inicio_referencias' => $string,
            ],
            'required' => [
                'titulo',
                'slug',
                'doi',
                'resumo',
                'autores',
                'endereco_instituicoes',
                'autor_correspondente',
                'palavras_chave',
                'areas_tematicas',
                'seo_titulo',
                'seo_descricao',
                'seo_palavras_chave',
                'documento_titulo',
                'marcador_inicio_corpo',
                'marcador_inicio_referencias',
            ],
        ];
    }
}
