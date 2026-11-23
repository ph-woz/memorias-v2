<?php

namespace App\DataTransferObjects;

use Illuminate\Http\UploadedFile;

readonly class CategoriaData
{
    public function __construct(
        public string $nome,
        public string $identificador,
        public ?UploadedFile $fotoArquivo,
        public ?string $seoTitulo,
        public ?string $seoDescricao,
        public ?string $seoPalavrasChave,
    ) {}

    /**
     * @param  array{
     *     nome: string,
     *     identificador?: string|null,
     *     foto?: UploadedFile|null,
     *     seo_titulo?: string|null,
     *     seo_descricao?: string|null,
     *     seo_palavras_chave?: string|null
     * }  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            nome: $payload['nome'],
            identificador: trim((string) ($payload['identificador'] ?? '')) !== ''
                ? (string) $payload['identificador']
                : $payload['nome'],
            fotoArquivo: $payload['foto'] ?? null,
            seoTitulo: self::nullableString($payload['seo_titulo'] ?? null),
            seoDescricao: self::nullableString($payload['seo_descricao'] ?? null),
            seoPalavrasChave: self::nullableString($payload['seo_palavras_chave'] ?? null),
        );
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $texto = trim((string) $value);

        return $texto !== '' ? $texto : null;
    }
}
