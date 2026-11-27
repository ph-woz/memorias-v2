@props([
    'quando',
    'mensagem',
])

@if (session('status') === $quando)
    <div {{ $attributes->merge(['class' => 'rounded-md bg-green-50 px-4 py-3 text-sm text-green-700']) }}>
        {{ $mensagem }}
        @if (session('abrir_site_url'))
            <a
                id="abrir-site-fallback"
                href="{{ session('abrir_site_url') }}"
                target="memorias_ver_no_site"
                rel="noopener noreferrer"
                class="ml-2 font-semibold underline"
            >{{ __('Abrir no site') }}</a>
        @endif
    </div>
@endif
