<?php

namespace App\Http\Controllers;

use App\Enums\Idioma;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;

class IdiomaController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validado = $request->validate([
            'idioma' => ['required', 'string', Rule::enum(Idioma::class)],
        ]);

        $idioma = Idioma::from($validado['idioma']);

        $request->session()->put((string) config('localization.session_key'), $idioma->value);
        App::setLocale($idioma->value);

        $destino = url()->previous() ?: route('home');

        return redirect()
            ->to($destino)
            ->withCookie(cookie(
                (string) config('localization.cookie'),
                $idioma->value,
                (int) config('localization.cookie_minutes'),
                '/',
                null,
                null,
                false,
                false,
                'lax',
            ));
    }
}
