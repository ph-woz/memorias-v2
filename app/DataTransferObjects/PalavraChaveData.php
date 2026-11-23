<?php

namespace App\DataTransferObjects;

readonly class PalavraChaveData
{
    public function __construct(
        public string $nome,
        public string $identificador,
    ) {}

    /**
     * @param  array{
     *     nome: string,
     *     identificador?: string|null
     * }  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            nome: $payload['nome'],
            identificador: trim((string) ($payload['identificador'] ?? '')) !== ''
                ? (string) $payload['identificador']
                : $payload['nome'],
        );
    }
}
