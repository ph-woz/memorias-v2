<?php

namespace App\Http\Requests\Artigos\Concerns;

use App\Enums\StatusArtigo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

trait NormalizaDadosArtigo
{
    /**
     * @return array{
     *     titulo: string,
     *     slug: string,
     *     doi: ?string,
     *     resumo: ?string,
     *     conteudo: string,
     *     endereco_instituicoes: ?string,
     *     autor_correspondente: ?string,
     *     status: StatusArtigo,
     *     categorias: list<int>,
     *     autores: list<string>,
     *     palavras_chave: list<string>,
     *     areas_tematicas: list<string>,
     *     etiquetas: list<string>,
     *     referencias: list<string>,
     *     documentos: list<array{id?: int, titulo: string, url?: string|null, arquivo?: UploadedFile|null}>,
     *     seo_titulo: ?string,
     *     seo_descricao: ?string,
     *     seo_palavras_chave: ?string,
     *     publicado_em: ?Carbon,
     *     foto: ?UploadedFile
     * }
     */
    public function dadosArtigo(): array
    {
        $payload = $this->validated();
        $status = StatusArtigo::from($payload['status']);

        return [
            'titulo' => $payload['titulo'],
            'slug' => trim((string) ($payload['slug'] ?? '')) !== ''
                ? (string) $payload['slug']
                : $payload['titulo'],
            'doi' => $this->textoOpcional($payload['doi'] ?? null),
            'resumo' => $this->textoOpcional($payload['resumo'] ?? null),
            'conteudo' => $payload['conteudo'],
            'endereco_instituicoes' => $this->textoOpcional($payload['endereco_instituicoes'] ?? null),
            'autor_correspondente' => $this->textoOpcional($payload['autor_correspondente'] ?? null),
            'status' => $status,
            'categorias' => $this->idsLimpos($payload['categorias'] ?? []),
            'autores' => $this->textosLimpos($payload['autores'] ?? []),
            'palavras_chave' => $this->textosLimpos($payload['palavras_chave'] ?? []),
            'areas_tematicas' => $this->textosLimpos($payload['areas_tematicas'] ?? []),
            'etiquetas' => $this->textosLimpos($payload['etiquetas'] ?? []),
            'referencias' => $this->textosLimpos($payload['referencias'] ?? []),
            'documentos' => $this->documentosLimpos($payload['documentos'] ?? []),
            'seo_titulo' => $this->textoOpcional($payload['seo_titulo'] ?? null),
            'seo_descricao' => $this->textoOpcional($payload['seo_descricao'] ?? null),
            'seo_palavras_chave' => $this->textoOpcional($payload['seo_palavras_chave'] ?? null),
            'publicado_em' => $status === StatusArtigo::Publicado
                ? (isset($payload['publicado_em']) && $payload['publicado_em'] !== ''
                    ? Carbon::parse($payload['publicado_em'])
                    : now())
                : null,
            'foto' => ($payload['foto'] ?? null) instanceof UploadedFile ? $payload['foto'] : null,
        ];
    }

    /**
     * @param  list<mixed>  $valores
     * @return list<int>
     */
    private function idsLimpos(array $valores): array
    {
        return array_values(array_unique(array_filter(
            array_map(static fn (mixed $valor): int => (int) $valor, $valores),
            static fn (int $id): bool => $id > 0,
        )));
    }

    /**
     * @param  list<mixed>  $valores
     * @return list<string>
     */
    private function textosLimpos(array $valores): array
    {
        return array_values(array_filter(
            array_map(
                static fn (mixed $valor): string => trim((string) $valor),
                $valores,
            ),
            static fn (string $valor): bool => $valor !== '',
        ));
    }

    /**
     * @param  list<mixed>  $documentos
     * @return list<array{id?: int, titulo: string, url?: string|null, arquivo?: UploadedFile|null}>
     */
    private function documentosLimpos(array $documentos): array
    {
        $limpos = [];

        foreach ($documentos as $documento) {
            if (! is_array($documento)) {
                continue;
            }

            $titulo = trim((string) ($documento['titulo'] ?? ''));

            if ($titulo === '') {
                continue;
            }

            $item = [
                'titulo' => $titulo,
                'url' => $this->textoOpcional($documento['url'] ?? null),
                'arquivo' => ($documento['arquivo'] ?? null) instanceof UploadedFile
                    ? $documento['arquivo']
                    : null,
            ];

            if (isset($documento['id']) && $documento['id'] !== '' && $documento['id'] !== null) {
                $item['id'] = (int) $documento['id'];
            }

            $limpos[] = $item;
        }

        return $limpos;
    }

    private function textoOpcional(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $texto = trim((string) $value);

        return $texto !== '' ? $texto : null;
    }
}
