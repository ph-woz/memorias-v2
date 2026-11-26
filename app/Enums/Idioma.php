<?php

namespace App\Enums;

enum Idioma: string
{
    case PtBr = 'pt_BR';
    case EnUs = 'en_US';
    case EsEs = 'es_ES';

    public function codigoBcp47(): string
    {
        return match ($this) {
            self::PtBr => 'pt-BR',
            self::EnUs => 'en-US',
            self::EsEs => 'es-ES',
        };
    }

    public function rotuloCurto(): string
    {
        return match ($this) {
            self::PtBr => 'PT',
            self::EnUs => 'EN',
            self::EsEs => 'ES',
        };
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::PtBr => 'Português',
            self::EnUs => 'English',
            self::EsEs => 'Español',
        };
    }

    /**
     * @return list<self>
     */
    public static function suportados(): array
    {
        return self::cases();
    }

    public static function padrao(): self
    {
        return self::PtBr;
    }

    public static function tentarDe(mixed $valor): ?self
    {
        if (! is_string($valor) || $valor === '') {
            return null;
        }

        $normalizado = str_replace('-', '_', trim($valor));

        return self::tryFrom($normalizado)
            ?? match (strtolower(str_replace('_', '-', $valor))) {
                'pt', 'pt-br', 'pt_br' => self::PtBr,
                'en', 'en-us', 'en_us', 'en-gb' => self::EnUs,
                'es', 'es-es', 'es_es', 'es-mx', 'es-ar' => self::EsEs,
                default => null,
            };
    }
}
