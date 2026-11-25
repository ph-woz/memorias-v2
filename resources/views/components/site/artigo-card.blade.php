@props([
    'artigo',
    'destaque' => false,
])

<a
    href="{{ route('site.artigos.show', $artigo) }}"
    {{ $attributes->class([
        'group block',
        'lg:grid lg:grid-cols-12 lg:gap-10 lg:items-center' => $destaque,
    ]) }}
>
    <div @class([
        'overflow-hidden bg-ink/5',
        'aspect-[16/10] lg:col-span-7 lg:aspect-[5/4]' => $destaque,
        'aspect-[4/3]' => ! $destaque,
    ])>
        @if ($artigo->url_foto)
            <img
                src="{{ $artigo->url_foto }}"
                alt=""
                class="h-full w-full object-cover transition duration-700 ease-editorial group-hover:scale-[1.04]"
            >
        @else
            <div class="flex h-full min-h-[10rem] items-center justify-center bg-gradient-to-br from-ink to-ink-soft text-sm text-paper/40">
                {{ __('Sem foto') }}
            </div>
        @endif
    </div>

    <div @class([
        'flex flex-col',
        'mt-6 lg:col-span-5 lg:mt-0' => $destaque,
        'mt-4' => ! $destaque,
    ])>
        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-accent-deep">
            {{ $artigo->categorias->pluck('nome')->join(' · ') ?: __('Memória') }}
            @if ($artigo->publicado_em)
                <span class="font-normal tracking-normal text-ink-muted">
                    · <time datetime="{{ $artigo->publicado_em->toDateString() }}">{{ $artigo->publicado_em->translatedFormat('d M Y') }}</time>
                </span>
            @elseif (! $artigo->estaPublicado())
                <span class="font-normal tracking-normal text-amber-700">
                    · {{ __('Rascunho') }}
                </span>
            @endif
        </p>

        <h3 @class([
            'mt-3 font-display font-semibold tracking-tight text-ink transition duration-300 group-hover:text-accent-deep',
            'text-display-feature' => $destaque,
            'text-xl leading-snug sm:text-2xl' => ! $destaque,
        ])>
            {{ $artigo->titulo }}
        </h3>

        @php
            $resumoCard = trim(html_entity_decode(strip_tags((string) $artigo->resumo), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        @endphp
        @if ($resumoCard !== '')
            <p @class([
                'mt-4 leading-relaxed text-ink-muted',
                'line-clamp-4 text-base sm:text-lg' => $destaque,
                'line-clamp-2 text-sm' => ! $destaque,
            ])>
                {{ $resumoCard }}
            </p>
        @endif

        @if ($artigo->autores->isNotEmpty())
            <p class="mt-5 text-xs font-medium text-ink/55">
                {{ $artigo->autores->pluck('nome')->take(3)->join(', ') }}
                @if ($artigo->autores->count() > 3)
                    <span class="text-ink/35">+{{ $artigo->autores->count() - 3 }}</span>
                @endif
            </p>
        @endif

        <span class="mt-5 inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-ink opacity-0 transition duration-500 group-hover:opacity-100">
            {{ __('Ler artigo') }}
            <span aria-hidden="true" class="transition group-hover:translate-x-1">→</span>
        </span>
    </div>
</a>
