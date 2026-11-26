@extends('layouts.site')

@section('title', $artigo->titulo.' — Memórias')

@php
    $resumoTextoPlano = trim(html_entity_decode(strip_tags((string) $artigo->resumo), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $descricaoMeta = $artigo->seo_descricao ?: ($resumoTextoPlano !== '' ? $resumoTextoPlano : null);
    $conteudoComCitacoes = app(\App\Support\Artigos\VinculaCitacoesNoConteudo::class)((string) $artigo->conteudo);
    $normalizarReferencia = app(\App\Support\Artigos\NormalizarTextoReferencia::class);
    $referenciasPainel = $artigo->referencias
        ->values()
        ->map(function ($referencia, int $indice) use ($normalizarReferencia): array {
            return [
                'numero' => $indice + 1,
                'texto' => $normalizarReferencia->normalizar((string) $referencia->texto),
            ];
        })
        ->all();
@endphp

@section('meta')
    @if ($descricaoMeta)
        <meta name="description" content="{{ $descricaoMeta }}">
    @endif
@endsection

@section('content')
    <div
        x-data
        x-init="$store.artigoReferencias.configurar(@js($referenciasPainel))"
    >
        <article class="pt-24">
            @unless ($artigo->estaPublicado())
                <div class="mx-auto max-w-6xl px-5 sm:px-8 lg:px-10">
                    <div class="mb-6 border border-amber-300/60 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        {{ __('Este artigo ainda é um rascunho.') }}
                    </div>
                </div>
            @endunless

            <header class="mx-auto max-w-6xl px-5 sm:px-8 lg:px-10">
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

                <h1 class="mt-5 font-display text-display-sm font-semibold leading-tight text-ink lg:text-[3.25rem] lg:leading-[1.12]">
                    {{ $artigo->titulo }}
                </h1>

                @if ($artigo->autores->isNotEmpty())
                    <p class="mt-6 text-sm font-medium text-ink-muted">
                        {{ $artigo->autores->pluck('nome')->join(', ') }}
                    </p>
                @endif

                @if (filled($artigo->endereco_instituicoes))
                    <div class="mt-4">
                        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-ink/45">{{ __('Endereço das instituições') }}</p>
                        <div class="conteudo-rico prose prose-stone mt-2 max-w-none text-sm leading-relaxed text-ink-muted prose-p:my-1 prose-a:text-accent-deep">
                            {!! $artigo->endereco_instituicoes !!}
                        </div>
                    </div>
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

            <div class="mx-auto max-w-6xl px-5 sm:px-8 lg:px-10">
                @if ($resumoTextoPlano !== '')
                    <div class="conteudo-rico conteudo-rico--resumo prose prose-stone mt-14 max-w-none font-display text-ink-muted prose-a:text-accent-deep">
                        {!! $artigo->resumo !!}
                    </div>
                @endif

                @if (filled($artigo->autor_correspondente))
                    <div class="mt-12 space-y-8 border-t border-ink/10 pt-10 text-base leading-relaxed text-ink-muted">
                        <div>
                            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-ink/45">{{ __('Autor correspondente') }}</p>
                            <div class="conteudo-rico prose prose-stone mt-4 max-w-none prose-a:text-accent-deep">
                                {!! $artigo->autor_correspondente !!}
                            </div>
                        </div>
                    </div>
                @endif

                <div
                    class="conteudo-rico prose prose-xl prose-stone mt-16 max-w-none prose-headings:font-display prose-headings:tracking-tight prose-a:text-accent-deep"
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
                                <li id="referencia-{{ $loop->iteration }}">{{ $normalizarReferencia->normalizar((string) $referencia->texto) }}</li>
                            @endforeach
                        </ol>
                    </section>
                @endif
            </div>
        </article>
    </div>

    {{-- Fora do wrapper do artigo: HTML importado pode quebrar o DOM e expulsar o aside. --}}
    @if ($artigo->referencias->isNotEmpty())
        <x-site.painel-referencias />
    @endif

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
