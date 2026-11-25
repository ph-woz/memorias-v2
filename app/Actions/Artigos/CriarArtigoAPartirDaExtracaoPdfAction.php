<?php

namespace App\Actions\Artigos;

use App\Enums\StatusArtigo;
use App\Models\Artigo;
use App\Models\ArtigoImportacao;
use App\Models\User;
use App\Support\Slug\UniqueSlugGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CriarArtigoAPartirDaExtracaoPdfAction
{
    public function __construct(
        private readonly UniqueSlugGenerator $slugGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $extracao
     */
    public function handle(ArtigoImportacao $importacao, array $extracao, User $usuario): Artigo
    {
        return DB::transaction(function () use ($importacao, $extracao, $usuario): Artigo {
            $titulo = $this->texto($extracao['titulo'] ?? null);

            if ($titulo === null) {
                throw new RuntimeException('Não é possível criar o artigo sem título literal extraído do PDF.');
            }

            $slugBase = $this->texto($extracao['slug'] ?? null) ?? $titulo;
            $autores = $this->listaTextos($extracao['autores'] ?? [], 255);

            if ($autores === []) {
                throw new RuntimeException('Não é possível criar o artigo sem autores literais extraídos do PDF.');
            }

            $conteudo = $this->texto($extracao['conteudo_html'] ?? null);

            if ($conteudo === null) {
                throw new RuntimeException('Não é possível criar o artigo sem conteúdo literal extraído do PDF.');
            }

            $documentoTitulo = $this->texto($extracao['documento_titulo'] ?? null)
                ?? pathinfo($importacao->pdf_nome_original, PATHINFO_FILENAME)
                ?: 'PDF do artigo';

            $caminhoDocumento = $this->moverPdfParaDocumentos($importacao);

            $artigo = Artigo::query()->create([
                'cadastrante_id' => $usuario->id,
                'titulo' => Str::limit($titulo, 255, ''),
                'slug' => $this->slugGenerator->generate(Artigo::class, $slugBase),
                'doi' => $this->normalizarDoi($this->texto($extracao['doi'] ?? null)),
                'resumo' => $this->texto($extracao['resumo'] ?? null),
                'conteudo' => $conteudo,
                'endereco_instituicoes' => $this->texto($extracao['endereco_instituicoes'] ?? null),
                'autor_correspondente' => $this->texto($extracao['autor_correspondente'] ?? null),
                'seo_titulo' => $this->limitar($this->texto($extracao['seo_titulo'] ?? null) ?? $titulo, 70),
                'seo_descricao' => $this->limitar(
                    $this->textoPlano($extracao['seo_descricao'] ?? null)
                        ?? $this->textoPlano($extracao['resumo'] ?? null),
                    160,
                ),
                'seo_palavras_chave' => $this->limitar($this->texto($extracao['seo_palavras_chave'] ?? null), 255),
                'status' => StatusArtigo::Rascunho,
                'publicado_em' => null,
            ]);

            $artigo->sincronizarAutores($autores);
            $artigo->sincronizarPalavrasChave($this->listaTextos($extracao['palavras_chave'] ?? [], 255));
            $artigo->sincronizarAreasTematicas($this->listaTextos($extracao['areas_tematicas'] ?? [], 255));
            $artigo->sincronizarReferencias($this->listaTextos($extracao['referencias'] ?? [], 10000));
            $artigo->documentos()->create([
                'titulo' => Str::limit($documentoTitulo, 255, ''),
                'arquivo' => $caminhoDocumento,
                'url' => null,
                'ordem' => 0,
            ]);

            $importacao->forceFill([
                'pdf_path' => $caminhoDocumento,
            ])->save();

            return $artigo->fresh([
                'autores',
                'palavrasChave',
                'areasTematicas',
                'referencias',
                'documentos',
            ]) ?? $artigo;
        });
    }

    private function moverPdfParaDocumentos(ArtigoImportacao $importacao): string
    {
        $origem = $importacao->pdf_path;
        $destino = 'artigos/documentos/'.Str::uuid()->toString().'.pdf';

        if (! Storage::disk('public')->exists($origem)) {
            throw new RuntimeException('O PDF da importação não foi encontrado no armazenamento.');
        }

        Storage::disk('public')->move($origem, $destino);

        return $destino;
    }

    private function texto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto !== '' ? $texto : null;
    }

    private function textoPlano(mixed $valor): ?string
    {
        $html = $this->texto($valor);

        if ($html === null) {
            return null;
        }

        $texto = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $texto = trim(preg_replace('/\s+/u', ' ', $texto) ?? $texto);

        return $texto !== '' ? $texto : null;
    }

    /**
     * @return list<string>
     */
    private function listaTextos(mixed $valores, int $max): array
    {
        if (! is_array($valores)) {
            return [];
        }

        $itens = [];

        foreach ($valores as $valor) {
            $texto = $this->texto($valor);

            if ($texto === null) {
                continue;
            }

            $itens[] = Str::limit($texto, $max, '');
        }

        return array_values($itens);
    }

    private function limitar(?string $valor, int $max): ?string
    {
        if ($valor === null) {
            return null;
        }

        return Str::limit($valor, $max, '');
    }

    private function normalizarDoi(?string $doi): ?string
    {
        if ($doi === null) {
            return null;
        }

        // Remove espaços inseridos pela extração tipográfica do PDF, sem alterar dígitos/pontuação.
        $doi = preg_replace('/\s+/u', '', $doi) ?? $doi;

        return $doi !== '' ? $doi : null;
    }
}
