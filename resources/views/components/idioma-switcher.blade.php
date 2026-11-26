@props([
    'variante' => 'admin',
    'sobreImagem' => false,
])

@php
    use App\Enums\Idioma;

    $atual = Idioma::tentarDe(app()->getLocale()) ?? Idioma::padrao();
    $ehSite = $variante === 'site';
    $ehSiteSobreImagem = $ehSite && $sobreImagem;
@endphp

<div
    {{ $attributes->class([
        'relative',
    ]) }}
    x-data="{ aberto: false }"
    @keydown.escape.window="aberto = false"
>
    <button
        type="button"
        @class([
            'inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-semibold uppercase tracking-[0.14em] transition focus:outline-none focus:ring-2 focus:ring-offset-1',
            'border border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900 focus:ring-indigo-500' => ! $ehSite,
            'border border-ink/15 bg-paper text-ink hover:border-ink/30 focus:ring-accent' => $ehSite && ! $ehSiteSobreImagem,
        ])
        @if ($ehSiteSobreImagem)
            :class="scrolled
                ? 'border-ink/15 bg-paper text-ink hover:border-ink/30 focus:ring-accent'
                : 'border-white/25 bg-white/10 text-white hover:bg-white/20 focus:ring-white/40'"
        @endif
        @click="aberto = ! aberto"
        @click.outside="aberto = false"
        :aria-expanded="aberto.toString()"
        aria-haspopup="listbox"
        aria-label="{{ __('Idioma') }}"
    >
        <span aria-hidden="true">{{ $atual->rotuloCurto() }}</span>
        <svg class="size-3.5 opacity-70 transition" :class="aberto ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
        </svg>
    </button>

    <div
        x-cloak
        x-show="aberto"
        x-transition.opacity.duration.150ms
        @class([
            'absolute right-0 z-50 mt-2 min-w-[10.5rem] overflow-hidden border py-1 shadow-md',
            'border-gray-200 bg-white text-gray-800' => ! $ehSite,
            'border-ink/10 bg-paper text-ink' => $ehSite,
        ])
        role="listbox"
        aria-label="{{ __('Idioma') }}"
    >
        @foreach (Idioma::suportados() as $idioma)
            <form method="POST" action="{{ route('idioma.update') }}">
                @csrf
                <input type="hidden" name="idioma" value="{{ $idioma->value }}">
                <button
                    type="submit"
                    class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm transition hover:bg-black/5"
                    @class([
                        'font-semibold text-indigo-700' => ! $ehSite && $idioma === $atual,
                        'font-semibold text-accent-deep' => $ehSite && $idioma === $atual,
                    ])
                    role="option"
                    aria-selected="{{ $idioma === $atual ? 'true' : 'false' }}"
                >
                    <span>{{ $idioma->rotulo() }}</span>
                    <span class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] opacity-60">{{ $idioma->rotuloCurto() }}</span>
                </button>
            </form>
        @endforeach
    </div>
</div>
