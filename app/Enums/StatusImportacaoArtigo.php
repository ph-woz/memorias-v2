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
            self::Pendente => 'Pendente',
            self::Processando => 'Processando',
            self::Concluida => 'Concluída',
            self::Falhou => 'Falhou',
        };
    }

    public function estaFinalizada(): bool
    {
        return $this === self::Concluida || $this === self::Falhou;
    }
}
