@props([
    'termo' => '',
    'variante' => 'header',
    'sobreImagem' => false,
    'autofocus' => false,
])

@php
    $ehHeader = $variante === 'header';
    $id = 'busca-artigos-'.$variante;
@endphp

@if ($ehHeader)
    <div class="flex items-center">
        <form
            method="GET"
            action="{{ route('site.busca') }}"
            class="group relative hidden sm:block"
            role="search"
        >
            <label for="{{ $id }}" class="sr-only">{{ __('Buscar artigos') }}</label>
            <div
                class="flex items-center border transition"
                :class="{{ $sobreImagem
                    ? "scrolled
                        ? 'border-ink/15 bg-paper/90 focus-within:border-ink/40'
                        : 'border-white/35 bg-white/10 focus-within:border-white focus-within:bg-white/15'"
                    : "'border-ink/15 bg-paper/80 focus-within:border-ink/40'" }}"
            >
                <span
                    class="pointer-events-none pl-3"
                    :class="{{ $sobreImagem
                        ? "scrolled ? 'text-ink/40' : 'text-white/70'"
                        : "'text-ink/40'" }}"
                    aria-hidden="true"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </span>
                <input
                    id="{{ $id }}"
                    type="search"
                    name="q"
                    value="{{ $termo }}"
                    maxlength="120"
                    placeholder="{{ __('Buscar artigos…') }}"
                    class="min-w-[12rem] border-0 bg-transparent py-2.5 pl-2 pr-3 text-sm focus:ring-0 lg:min-w-[16rem]"
                    :class="{{ $sobreImagem
                        ? "scrolled
                            ? 'text-ink placeholder:text-ink/40'
                            : 'text-white placeholder:text-white/55'"
                        : "'text-ink placeholder:text-ink/40'" }}"
                >
                <button
                    type="submit"
                    class="shrink-0 px-3 py-2 text-xs font-semibold uppercase tracking-[0.14em] transition"
                    :class="{{ $sobreImagem
                        ? "scrolled ? 'text-accent-deep hover:text-ink' : 'text-white/80 hover:text-white'"
                        : "'text-accent-deep hover:text-ink'" }}"
                >
                    {{ __('Buscar') }}
                </button>
            </div>
        </form>

        <a
            href="{{ route('site.busca') }}"
            class="inline-flex size-10 items-center justify-center border sm:hidden"
            :class="{{ $sobreImagem
                ? "scrolled ? 'border-ink/20 text-ink' : 'border-white/35 text-white'"
                : "'border-ink/20 text-ink'" }}"
            aria-label="{{ __('Buscar artigos') }}"
        >
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
            </svg>
        </a>
    </div>
@else
    <form
        method="GET"
        action="{{ route('site.busca') }}"
        {{ $attributes->class('w-full max-w-xl') }}
        role="search"
    >
        <label for="{{ $id }}" class="sr-only">{{ __('Buscar artigos') }}</label>
        <div class="flex items-center border border-ink/15 bg-paper transition focus-within:border-accent-deep">
            <span class="pointer-events-none pl-3 text-ink/40" aria-hidden="true">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                </svg>
            </span>
            <input
                id="{{ $id }}"
                type="search"
                name="q"
                value="{{ $termo }}"
                maxlength="120"
                placeholder="{{ __('Buscar artigos…') }}"
                class="w-full border-0 bg-transparent py-3.5 pl-2 pr-3 text-base text-ink placeholder:text-ink/40 focus:ring-0"
                @if ($autofocus) autofocus @endif
            >
            <button
                type="submit"
                class="shrink-0 px-4 py-3 text-xs font-semibold uppercase tracking-[0.14em] text-accent-deep transition hover:text-ink"
            >
                {{ __('Buscar') }}
            </button>
        </div>
    </form>
@endif
