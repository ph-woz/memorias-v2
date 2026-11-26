<?php

namespace App\Enums;

enum StatusImportacaoArtigo: string
{
    case Pendente = 'pendente';
    case Processando = 'processando';
    case Concluida = 'concluida';
    case Falhou = 'falhou';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => __('Pendente'),
            self::Processando => __('Processando'),
            self::Concluida => __('Concluída'),
            self::Falhou => __('Falhou'),
        };
    }

    public function estaFinalizada(): bool
    {
        return $this === self::Concluida || $this === self::Falhou;
    }
}
