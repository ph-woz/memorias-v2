<?php

namespace App\Jobs;

use App\Actions\Artigos\CriarArtigoAPartirDaExtracaoPdfAction;
use App\Ai\Agents\ExtrairArtigoDoPdf;
use App\Models\ArtigoImportacao;
use App\Support\Ai\ExtrairArtigoComGemini;
use App\Support\Ai\FormatarConteudoRicoComGemini;
use App\Support\Artigos\EnriquecerHtmlLiteralDoPdf;
use App\Support\Artigos\ExtrairImagensDoPdf;
use App\Support\Artigos\ExtrairTextoPdf;
use App\Support\Artigos\MontarArtigoLiteralDoPdf;
use App\Support\Artigos\RefinarMetadadosExtracaoPdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Files;
use Throwable;

class ProcessarImportacaoArtigoPdf implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 420;

    /** @var list<int> */
    public array $backoff = [20, 60, 120];

    public function __construct(public ArtigoImportacao $importacao) {}

    public function handle(
        CriarArtigoAPartirDaExtracaoPdfAction $criarArtigo,
        ExtrairTextoPdf $extrairTextoPdf,
        ExtrairArtigoComGemini $extrairComGemini,
        RefinarMetadadosExtracaoPdf $refinarMetadados,
        MontarArtigoLiteralDoPdf $montarLiteral,
        ExtrairImagensDoPdf $extrairImagens,
        FormatarConteudoRicoComGemini $formatarRico,
        EnriquecerHtmlLiteralDoPdf $enriquecerHtml,
    ): void {
        $this->importacao->refresh();

        if ($this->importacao->status->estaFinalizada()) {
            return;
        }

        $this->importacao->atualizarProgresso(5, 'Iniciando importação...');

        $usuario = $this->importacao->user;

        if ($usuario === null) {
            $this->importacao->marcarComoFalhou('Usuário da importação não encontrado.');

            return;
        }

        $provider = is_string(config('ai.default')) ? config('ai.default') : 'openai';
        $model = is_string(config('ai.models.text')) ? config('ai.models.text') : null;
        $modelGemini = filled($model) ? $model : 'gemini-3.5-flash-lite';

        $this->importacao->atualizarProgresso(15, 'Extraindo texto do PDF...');
        $textoPdf = $extrairTextoPdf->deCaminhoPublico($this->importacao->pdf_path);

        if (mb_strlen($textoPdf) < 500) {
            throw new \RuntimeException(
                'O PDF não possui texto extraível suficiente para importação literal fiel ao original.'
            );
        }

        $this->importacao->atualizarProgresso(30, 'Extraindo dados com IA...');
        $metadados = [];

        try {
            $metadados = $provider === 'gemini'
                ? $extrairComGemini->extrairMetadados($textoPdf, $modelGemini)
                : $this->extrairMetadadosComLaravelAi($textoPdf, $provider, $model);
        } catch (Throwable $exception) {
            Log::warning('IA indisponível na importação de PDF; usando heurísticas do layout.', [
                'importacao_id' => $this->importacao->id,
                'exception' => $exception->getMessage(),
            ]);
            $this->importacao->atualizarProgresso(40, 'IA indisponível; extraindo pelo layout do PDF...');
        }

        $this->importacao->atualizarProgresso(55, 'Organizando campos literais...');
        $metadados = $refinarMetadados->refinar($textoPdf, $metadados);

        if (trim((string) ($metadados['titulo'] ?? '')) === '') {
            throw new \RuntimeException('Não foi possível localizar o título literal do artigo no PDF.');
        }

        $extracao = $montarLiteral->montar($textoPdf, $metadados);

        $this->importacao->atualizarProgresso(65, 'Extraindo imagens do PDF...');
        $imagens = $extrairImagens->deCaminhoPublico($this->importacao->pdf_path);

        $this->importacao->atualizarProgresso(75, 'Formatando tipografia rica a partir do PDF...');
        $extracao = $this->aplicarTipografiaRica(
            $extracao,
            $textoPdf,
            $imagens,
            $provider,
            $modelGemini,
            $formatarRico,
            $enriquecerHtml,
        );

        $this->importacao->atualizarProgresso(90, 'Criando rascunho do artigo...');
        $artigo = $criarArtigo->handle($this->importacao, $extracao, $usuario);
        $this->importacao->marcarComoConcluida($artigo);
    }

    /**
     * @param  array<string, mixed>  $extracao
     * @param  list<array{path: string, url: string, alt?: string}>  $imagens
     * @return array<string, mixed>
     */
    private function aplicarTipografiaRica(
        array $extracao,
        string $textoPdf,
        array $imagens,
        string $provider,
        string $modelGemini,
        FormatarConteudoRicoComGemini $formatarRico,
        EnriquecerHtmlLiteralDoPdf $enriquecerHtml,
    ): array {
        $conteudoBase = (string) ($extracao['conteudo_html'] ?? '');
        $htmlLocal = $enriquecerHtml->polir($conteudoBase, $imagens);
        $htmlFinal = $htmlLocal;

        if ($provider === 'gemini') {
            try {
                $rico = $formatarRico->formatarComFallback(
                    $this->importacao->pdf_path,
                    $textoPdf,
                    $modelGemini,
                    $imagens,
                );

                $htmlGemini = trim((string) ($rico['conteudo_html'] ?? ''));

                if ($htmlGemini !== '' && $this->htmlRicoPreferivelAoLocal($htmlGemini, $htmlLocal)) {
                    $htmlFinal = $enriquecerHtml->polir($htmlGemini, $imagens);

                    if (filled($rico['resumo_html'])) {
                        $resumoTexto = trim(html_entity_decode(strip_tags($rico['resumo_html']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                        if ($resumoTexto !== '') {
                            $extracao['resumo'] = $resumoTexto;
                        }
                    }
                }
            } catch (Throwable $exception) {
                Log::warning('Formatação rica via Gemini falhou; mantendo tipografia local do PDF.', [
                    'importacao_id' => $this->importacao->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $extracao['conteudo_html'] = $htmlFinal;

        return $extracao;
    }

    private function htmlRicoPreferivelAoLocal(string $htmlGemini, string $htmlLocal): bool
    {
        $textoGemini = trim(html_entity_decode(strip_tags($htmlGemini), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $textoLocal = trim(html_entity_decode(strip_tags($htmlLocal), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($textoGemini === '' || mb_strlen($textoGemini) < 400) {
            return false;
        }

        // Descarta respostas que claramente misturam abstract/cabeçalho.
        if (preg_match('/\b(key words?|doi:|corresponding author)\b/iu', $textoGemini) === 1) {
            return false;
        }

        // Exige que o HTML da IA comece no mesmo trecho literal do corpo local.
        $localNorm = trim(preg_replace('/\s+/u', ' ', $textoLocal) ?? $textoLocal);
        $geminiNorm = trim(preg_replace('/\s+/u', ' ', $textoGemini) ?? $textoGemini);
        $inicioGemini = mb_substr($geminiNorm, 0, 48);
        $posInicio = $inicioGemini !== '' ? mb_stripos($localNorm, $inicioGemini) : false;

        if ($posInicio === false || $posInicio > 120) {
            return false;
        }

        $score = static function (string $html): int {
            return preg_match_all('/<em\b/i', $html)
                + preg_match_all('/<strong\b/i', $html)
                + preg_match_all('/<sup\b/i', $html)
                + preg_match_all('/<img\b/i', $html)
                + (int) min(30, preg_match_all('/<p\b/i', $html));
        };

        return $score($htmlGemini) >= max(12, (int) floor($score($htmlLocal) * 0.8));
    }

    /**
     * @return array<string, mixed>
     */
    private function extrairMetadadosComLaravelAi(string $textoPdf, string $provider, ?string $model): array
    {
        $prompt = "Localize metadados e marcadores literais no texto do PDF abaixo. Não reescreva nada. Não devolva o corpo do artigo.\n\nTEXTO DO PDF:\n"
            .Str::limit($textoPdf, 80000, '');

        try {
            $response = (new ExtrairArtigoDoPdf)->prompt(
                $prompt,
                attachments: mb_strlen($textoPdf) < 500
                    ? [Files\Document::fromStorage($this->importacao->pdf_path, 'public')]
                    : [],
                provider: $provider,
                model: filled($model) ? $model : null,
                timeout: 180,
            );
        } catch (ProviderOverloadedException $exception) {
            throw new \RuntimeException(
                'O provedor de IA está temporariamente sobrecarregado. Tente novamente em alguns minutos.',
                previous: $exception,
            );
        }

        return $response->toArray();
    }

    public function failed(?Throwable $exception): void
    {
        $mensagem = $exception?->getMessage() ?: 'Falha desconhecida ao importar o PDF.';

        Log::error('Falha ao processar importação de artigo via PDF.', [
            'importacao_id' => $this->importacao->id,
            'exception' => $mensagem,
        ]);

        $this->importacao->refresh();

        if (! $this->importacao->status->estaFinalizada()) {
            $this->importacao->marcarComoFalhou($mensagem);
        }
    }
}
