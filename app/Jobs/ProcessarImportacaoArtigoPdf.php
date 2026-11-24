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

        if ($provider === 'gemini') {
            try {
                $rico = $formatarRico->formatar(
                    $this->importacao->pdf_path,
                    $textoPdf,
                    $modelGemini,
                    $imagens,
                );

                if (trim(strip_tags($rico['conteudo_html'])) !== '') {
                    $htmlRico = $enriquecerHtml->repartirParagrafosLongos($rico['conteudo_html']);
                    $htmlRico = $enriquecerHtml->injetarImagensSeAusentes($htmlRico, $imagens);
                    $extracao['conteudo_html'] = $htmlRico;

                    // Resumo no site ainda é texto; preserva literal sem tags.
                    if (filled($rico['resumo_html'])) {
                        $resumoTexto = trim(html_entity_decode(strip_tags($rico['resumo_html']), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

                        if ($resumoTexto !== '') {
                            $extracao['resumo'] = $resumoTexto;
                        }
                    }

                    return $extracao;
                }
            } catch (Throwable $exception) {
                Log::warning('Formatação rica via Gemini falhou; aplicando enriquecimento local do PDF.', [
                    'importacao_id' => $this->importacao->id,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $extracao['conteudo_html'] = $enriquecerHtml->enriquecer($conteudoBase, $imagens);

        return $extracao;
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
