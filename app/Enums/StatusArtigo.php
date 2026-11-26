<?php

namespace App\Enums;

enum StatusArtigo: string
{
    case Rascunho = 'rascunho';
    case Publicado = 'publicado';

    public function label(): string
    {
        return match ($this) {
            self::Rascunho => __('Rascunho'),
            self::Publicado => __('Publicado'),
        };
    }
}
