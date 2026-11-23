<?php

namespace App\Enums;

enum PosicaoVertical: string
{
    case Topo = 'topo';
    case Centro = 'centro';
    case Baixo = 'baixo';

    public function label(): string
    {
        return match ($this) {
            self::Topo => 'Topo',
            self::Centro => 'Centro',
            self::Baixo => 'Baixo',
        };
    }

    public function classeItems(): string
    {
        return match ($this) {
            self::Topo => 'items-start',
            self::Centro => 'items-center',
            self::Baixo => 'items-end',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
