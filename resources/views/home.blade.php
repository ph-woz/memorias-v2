<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Memórias') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-stone-50 font-sans text-stone-900 antialiased">
        <header class="absolute inset-x-0 top-0 z-20">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-5 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight text-white drop-shadow">
                    {{ config('app.name', 'Memórias') }}
                </a>

                <nav class="flex items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-md bg-white/15 px-3 py-1.5 font-medium text-white backdrop-blur hover:bg-white/25">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-md bg-white/15 px-3 py-1.5 font-medium text-white backdrop-blur hover:bg-white/25">
                            Entrar
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        @if ($banners->isEmpty())
            <section class="flex min-h-[70vh] items-center justify-center bg-stone-900 px-6 text-center text-white">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl">{{ config('app.name', 'Memórias') }}</h1>
                    <p class="mt-3 text-white/70">Nenhum banner ativo no momento.</p>
                </div>
            </section>
        @else
            <div
                x-data="bannerCarousel({{ $banners->count() }})"
                class="relative"
            >
                <div class="relative overflow-hidden">
                    @foreach ($banners as $indice => $banner)
                        <div
                            x-show="atual === {{ $indice }}"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            @if ($indice !== 0) style="display: none;" @endif
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
                    <div class="absolute inset-x-0 bottom-6 z-20 flex justify-center gap-2">
                        @foreach ($banners as $indice => $banner)
                            <button
                                type="button"
                                class="h-2.5 w-2.5 rounded-full transition"
                                :class="atual === {{ $indice }} ? 'bg-white' : 'bg-white/40 hover:bg-white/70'"
                                @click="irPara({{ $indice }})"
                                aria-label="Ir para banner {{ $indice + 1 }}"
                            ></button>
                        @endforeach
                    </div>

                    <button
                        type="button"
                        class="absolute left-4 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/30 p-2 text-white backdrop-blur hover:bg-black/45"
                        @click="anterior()"
                        aria-label="Banner anterior"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
                    </button>
                    <button
                        type="button"
                        class="absolute right-4 top-1/2 z-20 -translate-y-1/2 rounded-full bg-black/30 p-2 text-white backdrop-blur hover:bg-black/45"
                        @click="proximo()"
                        aria-label="Próximo banner"
                    >
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
                    </button>
                @endif
            </div>
        @endif

        <main class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-semibold tracking-tight text-stone-900">Memórias</h2>
            <p class="mt-2 max-w-2xl text-stone-600">
                A home do blog exibe os banners ativos cadastrados no painel. Gerencie imagem, overlay, título, slogan e CTA em Banners.
            </p>
        </main>
    </body>
</html>
