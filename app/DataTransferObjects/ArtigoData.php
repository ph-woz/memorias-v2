<?php

namespace App\DataTransferObjects;

use App\Enums\StatusArtigo;
use Illuminate\Support\Carbon;

readonly class ArtigoData
{
    /**
     * @param  list<int>  $autorIds
     * @param  list<int>  $palavraChaveIds
     */
    public function __construct(
        public string $titulo,
        public ?string $resumo,
        public string $conteudo,
        public StatusArtigo $status,
        public ?int $categoriaId,
        public array $autorIds,
        public array $palavraChaveIds,
        public ?Carbon $publicadoEm,
    ) {}

    /**
     * @param  array{
     *     titulo: string,
     *     resumo?: string|null,
     *     conteudo: string,
     *     status: string,
     *     categoria_id?: int|null,
     *     autor_ids: list<int>,
     *     palavra_chave_ids?: list<int>|null,
     *     publicado_em?: string|null
     * }  $payload
     */
    public static function fromArray(array $payload): self
    {
        $status = StatusArtigo::from($payload['status']);

        return new self(
            titulo: $payload['titulo'],
            resumo: $payload['resumo'] ?? null,
            conteudo: $payload['conteudo'],
            status: $status,
            categoriaId: isset($payload['categoria_id']) ? (int) $payload['categoria_id'] : null,
            autorIds: array_values(array_unique(array_map('intval', $payload['autor_ids']))),
            palavraChaveIds: array_map('intval', $payload['palavra_chave_ids'] ?? []),
            publicadoEm: $status === StatusArtigo::Publicado
                ? (isset($payload['publicado_em']) && $payload['publicado_em'] !== ''
                    ? Carbon::parse($payload['publicado_em'])
                    : now())
                : null,
        );
    }
}
