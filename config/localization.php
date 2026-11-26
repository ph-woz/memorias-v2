<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cookie da preferência de idioma
    |--------------------------------------------------------------------------
    |
    | Persistido por um ano após a primeira detecção ou troca manual.
    |
    */

    'cookie' => env('LOCALE_COOKIE', 'memorias_locale'),

    'cookie_minutes' => (int) env('LOCALE_COOKIE_MINUTES', 60 * 24 * 365),

    /*
    |--------------------------------------------------------------------------
    | Chave de sessão
    |--------------------------------------------------------------------------
    */

    'session_key' => 'locale',

];
