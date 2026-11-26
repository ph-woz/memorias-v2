<?php

namespace App\Http\Middleware;

use App\Enums\Idioma;
use App\Support\Idioma\ResolvedorIdioma;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class DefinirIdioma
{
    public function __construct(private ResolvedorIdioma $resolvedor) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionKey = (string) config('localization.session_key');
        $cookieName = (string) config('localization.cookie');

        $idioma = $this->resolvedor->resolver($request);

        $request->session()->put($sessionKey, $idioma->value);
        App::setLocale($idioma->value);
        Carbon::setLocale($idioma->value);

        /** @var Response $response */
        $response = $next($request);

        $idiomaFinal = Idioma::tentarDe($request->session()->get($sessionKey)) ?? $idioma;

        if ($idiomaFinal !== $idioma) {
            App::setLocale($idiomaFinal->value);
            Carbon::setLocale($idiomaFinal->value);
        }

        if ($request->cookie($cookieName) !== $idiomaFinal->value) {
            $response->headers->setCookie($this->cookie($idiomaFinal));
        }

        return $response;
    }

    private function cookie(Idioma $idioma): Cookie
    {
        return cookie(
            (string) config('localization.cookie'),
            $idioma->value,
            (int) config('localization.cookie_minutes'),
            '/',
            null,
            null,
            false,
            false,
            'lax',
        );
    }
}
