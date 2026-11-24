@props([
    'itens',
    'sobreImagem' => false,
])

@php
    $linkBase = 'site-link inline-flex items-center gap-1 px-2 py-2 text-sm font-medium transition';
    // Branco no hero; tinta quando o header ganha fundo sólido (scroll) ou em páginas sem hero.
    $corLink = $sobreImagem
        ? "scrolled ? 'text-ink hover:text-ink' : 'text-white hover:text-white'"
        : "'text-ink hover:text-ink'";
@endphp

<nav class="hidden items-center gap-1 md:flex sm:gap-2">
    @foreach ($itens as $item)
        @if ($item->temSublinks())
            <div class="relative" x-data="{ aberto: false }" @keydown.escape.window="aberto = false">
                <button
                    type="button"
                    class="{{ $linkBase }}"
                    :class="{{ $corLink }}"
                    @click="aberto = !aberto"
                    @click.outside="aberto = false"
                    :aria-expanded="aberto.toString()"
                >
                    <span>{{ $item->rotulo }}</span>
                    <svg class="size-3.5 transition" :class="aberto ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m19.5 8.25-7.5 7.5-7.5-7.5"/>
                    </svg>
                </button>

                <div
                    x-cloak
                    x-show="aberto"
                    x-transition.opacity.duration.150ms
                    class="absolute left-0 top-full z-50 mt-2 min-w-[12rem] border border-ink/10 bg-paper py-2 text-ink shadow-sm"
                >
                    @foreach ($item->filhos as $filho)
                        @if ($filho->temUrl())
                            <a
                                href="{{ $filho->url }}"
                                @if ($filho->target_blank) target="_blank" rel="noopener noreferrer" @endif
                                class="block px-4 py-2 text-sm text-ink/80 transition hover:bg-ink/5 hover:text-ink"
                            >
                                {{ $filho->rotulo }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        @elseif ($item->temUrl())
            <a
                href="{{ $item->url }}"
                class="{{ $linkBase }}"
                :class="{{ $corLink }}"
                @if ($item->target_blank) target="_blank" rel="noopener noreferrer" @endif
            >
                {{ $item->rotulo }}
            </a>
        @endif
    @endforeach
</nav>

<div class="md:hidden" x-data="{ aberto: false }">
    <button
        type="button"
        class="inline-flex size-10 items-center justify-center border transition"
        :class="{{ $sobreImagem
            ? "scrolled ? 'border-ink/20 text-ink' : 'border-white/35 text-white'"
            : "'border-ink/20 text-ink'" }}"
        @click="aberto = !aberto"
        :aria-expanded="aberto.toString()"
        aria-label="{{ __('Abrir menu') }}"
    >
        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
        </svg>
    </button>

    <div
        x-cloak
        x-show="aberto"
        x-transition.opacity.duration.150ms
        class="absolute inset-x-0 top-full z-50 border-b border-ink/10 bg-paper text-ink shadow-sm"
        @click.outside="aberto = false"
    >
        <div class="mx-auto flex max-w-site flex-col gap-1 px-5 py-4 sm:px-8">
            @foreach ($itens as $item)
                @if ($item->temSublinks())
                    <div x-data="{ subAberto: false }" class="border-b border-ink/5 py-2">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between py-2 text-left text-sm font-medium text-ink"
                            @click="subAberto = !subAberto"
                        >
                            <span>{{ $item->rotulo }}</span>
                            <svg class="size-4 transition" :class="subAberto ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m19.5 8.25-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="subAberto" x-cloak class="space-y-1 pb-2 pl-3">
                            @foreach ($item->filhos as $filho)
                                @if ($filho->temUrl())
                                    <a
                                        href="{{ $filho->url }}"
                                        @if ($filho->target_blank) target="_blank" rel="noopener noreferrer" @endif
                                        class="block py-1.5 text-sm text-ink/70"
                                    >
                                        {{ $filho->rotulo }}
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @elseif ($item->temUrl())
                    <a
                        href="{{ $item->url }}"
                        @if ($item->target_blank) target="_blank" rel="noopener noreferrer" @endif
                        class="border-b border-ink/5 py-3 text-sm font-medium text-ink"
                    >
                        {{ $item->rotulo }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
</div>
