@extends('layouts.site')

@section('title', $artigo->titulo.' — Memórias')

@section('meta')
    @if ($artigo->seo_descricao ?: $artigo->resumo)
        <meta name="description" content="{{ $artigo->seo_descricao ?: $artigo->resumo }}">
    @endif
@endsection

@php
    $conteudoComCitacoes = app(\App\Support\Artigos\VinculaCitacoesNoConteudo::class)((string) $artigo->conteudo);
    $referenciasPainel = $artigo->referencias
        ->values()
        ->map(static fn ($referencia, int $indice): array => [
            'numero' => $indice + 1,
            'texto' => (string) $referencia->texto,
        ])
        ->all();
@endphp

@section('content')
    <div x-data="artigoReferenciasPainel(@js($referenciasPainel))">
        <article class="pt-24">
            @unless ($artigo->estaPublicado())
                <div class="mx-auto max-w-3xl px-5 sm:px-8">
                    <div class="mb-6 border border-amber-300/60 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        {{ __('Este artigo ainda é um rascunho.') }}
                    </div>
                </div>
            @endunless

            <header class="mx-auto max-w-3xl px-5 sm:px-8">
                <p class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-accent-deep">
                    @if ($artigo->categorias->isNotEmpty())
                        @foreach ($artigo->categorias as $categoria)
                            @if (! $loop->first)
                                <span class="text-ink-muted"> · </span>
                            @endif
                            <a href="{{ route('site.categorias.show', $categoria) }}" class="hover:text-ink">
                                {{ $categoria->nome }}
                            </a>
                        @endforeach
                    @else
                        {{ __('Memória') }}
                    @endif
                    @if ($artigo->publicado_em)
                        <span class="font-normal tracking-normal text-ink-muted">
                            · <time datetime="{{ $artigo->publicado_em->toDateString() }}">{{ $artigo->publicado_em->translatedFormat('d M Y') }}</time>
                        </span>
                    @endif
                </p>

                <h1 class="mt-5 font-display text-display font-semibold text-ink">
                    {{ $artigo->titulo }}
                </h1>

                @if ($artigo->autores->isNotEmpty())
                    <p class="mt-6 text-sm font-medium text-ink-muted">
                        {{ $artigo->autores->pluck('nome')->join(', ') }}
                    </p>
                @endif

                @if ($artigo->doi)
                    <p class="mt-4 text-sm text-ink-muted">
                        <span class="font-semibold text-ink">DOI:</span>
                        <a
                            href="https://doi.org/{{ ltrim($artigo->doi, '/') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-accent-deep underline decoration-accent/30 underline-offset-4 hover:text-ink"
                        >
                            {{ $artigo->doi }}
                        </a>
                    </p>
                @endif

                @if ($artigo->documentos->isNotEmpty())
                    <div class="mt-6 flex flex-wrap items-center gap-2">
                        @foreach ($artigo->documentos as $documento)
                            @continue(! $documento->url_arquivo)
                            <a
                                href="{{ $documento->url_arquivo }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                title="{{ $documento->titulo }}"
                                class="inline-flex min-w-[3.5rem] items-center justify-center bg-accent-deep px-3 py-1.5 text-[0.7rem] font-semibold uppercase tracking-[0.14em] text-white transition hover:bg-ink"
                            >
                                {{ $documento->rotuloBotao() }}
                            </a>
                        @endforeach
                    </div>
                @endif

                @auth
                    <div class="mt-4">
                        <a href="{{ route('artigos.edit', $artigo) }}" class="text-sm font-medium text-accent-deep hover:text-ink">
                            {{ __('Editar no painel') }}
                        </a>
                    </div>
                @endauth
            </header>

            @if ($artigo->url_foto)
                <div class="mx-auto mt-12 max-w-5xl px-5 sm:px-8">
                    <img
                        src="{{ $artigo->url_foto }}"
                        alt=""
                        class="aspect-[21/9] w-full object-cover"
                    >
                </div>
            @endif

            <div class="mx-auto max-w-3xl px-5 sm:px-8">
                @if ($artigo->resumo)
                    <p class="mt-12 font-display text-2xl leading-snug text-ink-muted sm:text-3xl">
                        {{ $artigo->resumo }}
                    </p>
                @endif

                <div
                    class="conteudo-rico prose prose-stone mt-12 max-w-none prose-headings:font-display prose-headings:tracking-tight prose-a:text-accent-deep"
                    @click="tratarCliqueCitacao($event)"
                >
                    {!! $conteudoComCitacoes !!}
                </div>

                @if ($artigo->etiquetas->isNotEmpty())
                    <div class="mt-12 flex flex-wrap gap-2">
                        @foreach ($artigo->etiquetas as $etiqueta)
                            <span
                                class="px-2.5 py-1 text-xs font-medium text-white"
                                style="background-color: {{ $etiqueta->cor }}"
                            >
                                {{ $etiqueta->nome }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if ($artigo->areasTematicas->isNotEmpty())
                    <div class="mt-10 border-t border-ink/10 pt-8">
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-ink/45">{{ __('Áreas temáticas') }}</p>
                        <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2">
                            @foreach ($artigo->areasTematicas as $areaTematica)
                                <a
                                    href="{{ route('site.areas_tematicas.show', $areaTematica) }}"
                                    class="text-sm font-medium text-accent-deep underline decoration-accent/30 underline-offset-4 hover:text-ink"
                                >
                                    {{ $areaTematica->nome }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($artigo->palavrasChave->isNotEmpty())
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($artigo->palavrasChave as $palavraChave)
                            <span class="bg-ink/5 px-2.5 py-1 text-xs font-medium text-ink-muted">
                                {{ $palavraChave->nome }}
                            </span>
                        @endforeach
                    </div>
                @endif

                @if ($artigo->referencias->isNotEmpty())
                    <section class="mt-14 border-t border-ink/10 pt-10">
                        <h2 class="font-display text-2xl font-semibold text-ink">{{ __('Referências') }}</h2>
                        <ol class="mt-5 list-decimal space-y-3 pl-5 text-sm leading-relaxed text-ink-muted">
                            @foreach ($artigo->referencias as $referencia)
                                <li id="referencia-{{ $loop->iteration }}">{{ $referencia->texto }}</li>
                            @endforeach
                        </ol>
                    </section>
                @endif
            </div>
        </article>

        @if ($artigo->referencias->isNotEmpty())
            <x-site.painel-referencias :referencias="$artigo->referencias" />
        @endif
    </div>

    @if ($relacionados->isNotEmpty())
        <section class="mt-20 border-t border-ink/10 bg-paper-warm/60 py-16">
            <div class="mx-auto max-w-site px-5 sm:px-8 lg:px-10">
                <h2 class="font-display text-display-sm font-semibold tracking-tight text-ink">
                    {{ __('Continue lendo') }}
                </h2>
                <div class="mt-12 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($relacionados as $relacionado)
                        <x-site.artigo-card :artigo="$relacionado" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
