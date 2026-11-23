<?php

namespace App\DataTransferObjects;

use App\Enums\StatusUsuario;

readonly class UsuarioData
{
    public function __construct(
        public string $nome,
        public string $nickname,
        public string $email,
        public ?string $password,
        public bool $emailVerificado,
        public StatusUsuario $status,
    ) {}

    /**
     * @param  array{
     *     nome: string,
     *     nickname: string,
     *     email: string,
     *     password?: string|null,
     *     email_verificado?: bool|int|string|null,
     *     status?: int|string|null
     * }  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            nome: $payload['nome'],
            nickname: $payload['nickname'],
            email: $payload['email'],
            password: filled($payload['password'] ?? null) ? (string) $payload['password'] : null,
            emailVerificado: filter_var($payload['email_verificado'] ?? false, FILTER_VALIDATE_BOOL),
            status: StatusUsuario::from((int) ($payload['status'] ?? StatusUsuario::Ativo->value)),
        );
    }
}
