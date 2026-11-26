<?php

namespace App\Support\Idioma;

use App\Enums\Idioma;
use Illuminate\Http\Request;

class ResolvedorIdioma
{
    public function resolver(Request $request): Idioma
    {
        $daSessao = Idioma::tentarDe($request->session()->get(config('localization.session_key')));

        if ($daSessao !== null) {
            return $daSessao;
        }

        $doCookie = Idioma::tentarDe($request->cookie(config('localization.cookie')));

        if ($doCookie !== null) {
            return $doCookie;
        }

        return $this->doNavegador($request) ?? Idioma::padrao();
    }

    public function doNavegador(Request $request): ?Idioma
    {
        $preferidos = $request->getLanguages();

        foreach ($preferidos as $preferido) {
            $idioma = Idioma::tentarDe($preferido);

            if ($idioma !== null) {
                return $idioma;
            }

            $base = strtolower(explode('_', str_replace('-', '_', $preferido))[0] ?? '');
            $idioma = Idioma::tentarDe($base);

            if ($idioma !== null) {
                return $idioma;
            }
        }

        $preferidoLaravel = $request->getPreferredLanguage(
            array_map(static fn (Idioma $idioma): string => $idioma->value, Idioma::suportados()),
        );

        return Idioma::tentarDe($preferidoLaravel);
    }
}
