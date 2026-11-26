<?php

namespace App\Enums;

enum PosicaoHorizontal: string
{
    case Esquerda = 'esquerda';
    case Centro = 'centro';
    case Direita = 'direita';

    public function label(): string
    {
        return match ($this) {
            self::Esquerda => __('Esquerda'),
            self::Centro => __('Centro'),
            self::Direita => __('Direita'),
        };
    }

    public function classeJustify(): string
    {
        return match ($this) {
            self::Esquerda => 'justify-start',
            self::Centro => 'justify-center',
            self::Direita => 'justify-end',
        };
    }

    public function classeTexto(): string
    {
        return match ($this) {
            self::Esquerda => 'text-left',
            self::Centro => 'text-center',
            self::Direita => 'text-right',
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
