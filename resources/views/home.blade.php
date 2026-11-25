@extends('layouts.site', ['headerSobreImagem' => $banners->isNotEmpty()])

@section('title', 'Home - Memórias')

@section('meta')
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="Home - Memórias">
    <meta property="og:type" content="website">
@endsection

@section('content')
    @if ($banners->isEmpty())
        <section class="relative flex min-h-[100svh] items-end overflow-hidden bg-ink px-5 pb-20 pt-32 text-white sm:px-8 lg:px-10">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(45,212,191,0.18),transparent_50%),linear-gradient(160deg,#071512_0%,#14302a_55%,#0c2923_100%)]"></div>
            <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-ink to-transparent"></div>
            <div class="relative mx-auto w-full max-w-site animate-fade-up">
                <p class="font-display text-display font-semibold">Memórias</p>
                <p class="mt-5 max-w-xl text-lg text-white/70">
                    {{ __('Histórias científicas que atravessam gerações — microbiologia, vetores e a saúde humana.') }}
                </p>
                <a
                    href="#ultimos"
                    class="mt-10 inline-flex items-center gap-3 border border-white/30 bg-white px-6 py-3 text-xs font-semibold uppercase tracking-[0.16em] text-ink transition hover:bg-transparent hover:text-white"
                >
                    {{ __('Explorar o acervo') }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </section>
    @else
        <div
            x-data="bannerCarousel({{ $banners->count() }})"
            class="relative min-h-[100svh]"
            @mouseenter="pararAutoplay()"
            @mouseleave="iniciarAutoplay()"
        >
            <div class="relative min-h-[100svh]">
                @foreach ($banners as $indice => $banner)
                    <div
                        class="absolute inset-0 transition-opacity duration-700 ease-editorial"
                        :class="atual === {{ $indice }}
                            ? 'z-10 banner-slide-active pointer-events-auto'
                            : 'z-0 pointer-events-none'"
                        :style="`opacity: ${atual === {{ $indice }} ? 1 : 0}`"
                        @if ($indice === 0)
                            style="opacity: 1"
                        @else
                            style="opacity: 0"
                        @endif
                    >
                        <x-banner-slide
                            :titulo="$banner->titulo"
                            :slogan="$banner->slogan ?? ''"
                            :imagem-url="$banner->url_imagem"
                            :overlay-cor="$banner->overlay_cor"
                            :overlay-opacidade="$banner->overlay_opacidade"
                            :posicao-horizontal="$banner->posicao_horizontal"
                            :posicao-vertical="$banner->posicao_vertical"
                            :cta-texto="$banner->cta_texto ?? ''"
                            :cta-link="$banner->cta_link ?? ''"
                        />
                    </div>
                @endforeach
            </div>

            @if ($banners->count() > 1)
                <div class="absolute inset-x-0 bottom-0 z-20">
                    <div class="mx-auto flex max-w-site items-center justify-between gap-6 px-5 py-6 sm:px-8 lg:px-10">
                        <p class="font-mono text-xs tracking-[0.2em] text-white/70">
                            <span x-text="String(atual + 1).padStart(2, '0')"></span>
                            <span class="mx-2 text-white/30">/</span>
                            <span>{{ str_pad((string) $banners->count(), 2, '0', STR_PAD_LEFT) }}</span>
                        </p>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex size-11 items-center justify-center border border-white/25 text-white transition hover:bg-white hover:text-ink"
                                @click="anterior()"
                                aria-label="Banner anterior"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                            </button>
                            <button
                                type="button"
                                class="inline-flex size-11 items-center justify-center border border-white/25 text-white transition hover:bg-white hover:text-ink"
                                @click="proximo()"
                                aria-label="Próximo banner"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="h-0.5 w-full bg-white/15">
                        <div
                            class="h-full bg-accent-bright transition-all duration-700 ease-editorial"
                            :style="`width: ${((atual + 1) / total) * 100}%`"
                        ></div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <main>
        <section id="ultimos" class="scroll-mt-28 px-5 pt-20 sm:px-8 sm:pt-28 lg:px-10">
            <div class="reveal-on-scroll mx-auto max-w-site">
                <div class="flex flex-col gap-4 border-b border-ink/10 pb-8 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-accent-deep">{{ __('Acervo') }}</p>
                        <h2 class="mt-3 font-display text-display-sm font-semibold text-ink">
                            {{ __('Últimas publicações') }}
                        </h2>
                    </div>
                    <p class="max-w-md text-sm leading-relaxed text-ink-muted lg:text-right">
                        {{ __('Leituras recentes do periódico — pesquisa, contexto e continuidade científica.') }}
                    </p>
                </div>

                <div class="mt-8">
                    <x-site.busca-form variante="home" />
                </div>

                @if ($destaque)
                    <div class="mt-12">
                        <x-site.artigo-card :artigo="$destaque" destaque />
                    </div>
                @endif

                @if ($artigos->isNotEmpty())
                    <div class="mt-16 grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($artigos as $artigo)
                            <div class="reveal-on-scroll" style="transition-delay: {{ min($loop->index * 80, 240) }}ms">
                                <x-site.artigo-card :artigo="$artigo" />
                            </div>
                        @endforeach
                    </div>
                @elseif (! $destaque)
                    <p class="mt-12 border border-dashed border-ink/15 px-6 py-16 text-center text-sm text-ink-muted">
                        {{ __('Ainda não há artigos publicados.') }}
                    </p>
                @endif
            </div>
        </section>

        <section id="categorias" class="scroll-mt-28 px-5 pt-24 sm:px-8 sm:pt-32 lg:px-10">
            <div class="reveal-on-scroll mx-auto max-w-site">
                <div class="border-b border-ink/10 pb-8">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-accent-deep">{{ __('Navegar') }}</p>
                    <h2 class="mt-3 font-display text-display-sm font-semibold text-ink">
                        {{ __('Categorias') }}
                    </h2>
                </div>

                <div class="mt-10 grid gap-px bg-ink/10 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($categorias as $categoria)
                        <a
                            href="{{ route('site.categorias.show', $categoria) }}"
                            class="group relative min-h-[14rem] overflow-hidden bg-ink text-white"
                        >
                            @if ($categoria->url_foto)
                                <img
                                    src="{{ $categoria->url_foto }}"
                                    alt=""
                                    class="absolute inset-0 h-full w-full object-cover opacity-50 transition duration-700 ease-editorial group-hover:scale-105 group-hover:opacity-60"
                                >
                            @else
                                <div class="absolute inset-0 bg-gradient-to-br from-ink-soft to-ink"></div>
                            @endif
                            <div class="absolute inset-0 bg-gradient-to-t from-ink via-ink/30 to-transparent"></div>
                            <div class="relative flex h-full min-h-[14rem] flex-col justify-end p-6">
                                <h3 class="font-display text-2xl font-semibold tracking-tight">{{ $categoria->nome }}</h3>
                                <p class="mt-2 text-xs uppercase tracking-[0.16em] text-white/55">
                                    {{ trans_choice(':count artigo|:count artigos', $categoria->total_publicados, ['count' => $categoria->total_publicados]) }}
                                </p>
                            </div>
                        </a>
                    @empty
                        <p class="col-span-full bg-paper px-6 py-12 text-sm text-ink-muted">{{ __('Nenhuma categoria disponível.') }}</p>
                    @endforelse
                </div>
            </div>
        </section>

        @if ($areasTematicas->isNotEmpty())
            <section id="temas" class="scroll-mt-28 px-5 py-24 sm:px-8 sm:py-32 lg:px-10">
                <div class="reveal-on-scroll mx-auto max-w-site">
                    <div class="border-b border-ink/10 pb-8">
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-accent-deep">{{ __('Temas') }}</p>
                        <h2 class="mt-3 font-display text-display-sm font-semibold text-ink">
                            {{ __('Áreas temáticas') }}
                        </h2>
                    </div>

                    <div class="mt-4 divide-y divide-ink/10">
                        @foreach ($areasTematicas as $area)
                            <a
                                href="{{ route('site.areas_tematicas.show', $area) }}"
                                class="group flex items-baseline justify-between gap-6 py-6 transition"
                            >
                                <span class="font-display text-2xl text-ink transition group-hover:text-accent-deep sm:text-3xl">
                                    {{ $area->nome }}
                                </span>
                                <span class="shrink-0 text-xs font-semibold uppercase tracking-[0.16em] text-ink/35 transition group-hover:text-accent-deep">
                                    {{ $area->total_publicados }}
                                    <span class="ml-3 inline-block transition group-hover:translate-x-1" aria-hidden="true">→</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="px-5 pb-8 sm:px-8 lg:px-10">
            <div class="reveal-on-scroll relative mx-auto max-w-site overflow-hidden bg-ink px-8 py-16 text-white sm:px-14 sm:py-20">
                <div class="pointer-events-none absolute -right-20 -top-20 size-72 rounded-full bg-accent/20 blur-3xl"></div>
                <div class="relative max-w-2xl">
                    <h2 class="font-display text-display-sm font-semibold tracking-tight">
                        {{ __('Ciência que permanece acessível.') }}
                    </h2>
                    <p class="mt-4 text-base leading-relaxed text-white/65">
                        {{ __('Explore artigos, temas e publicações do acervo editorial das Memórias.') }}
                    </p>
                </div>
            </div>
        </section>
    </main>
@endsection
