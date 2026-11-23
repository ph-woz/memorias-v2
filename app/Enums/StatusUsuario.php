<?php

namespace App\Enums;

enum StatusUsuario: int
{
    case Ativo = 0;
    case Desativado = 1;

    public function label(): string
    {
        return match ($this) {
            self::Ativo => 'Ativo',
            self::Desativado => 'Desativado',
        };
    }

    public function estaAtivo(): bool
    {
        return $this === self::Ativo;
    }

    /**
     * @return list<int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
