@extends('layouts.site')

@section('title', ($buscou ? __('Busca: :termo', ['termo' => $termo]) : __('Buscar artigos')).' — Memórias')

@section('content')
    <div class="pt-28 sm:pt-32">
        <div class="mx-auto max-w-site px-5 sm:px-8 lg:px-10">
            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.22em] text-accent-deep">{{ __('Acervo') }}</p>
            <h1 class="mt-3 font-display text-display-sm font-semibold tracking-tight text-ink">
                {{ __('Buscar artigos') }}
            </h1>
            <p class="mt-4 max-w-2xl text-base text-ink-muted">
                {{ __('Pesquise por título, resumo, autor, DOI, palavra-chave ou área temática.') }}
            </p>

            <div class="mt-10">
                <x-site.busca-form :termo="$termo" variante="pagina" autofocus />
            </div>

            <div class="mt-14">
                @if (! $buscou)
                    <p class="border border-dashed border-ink/15 px-6 py-16 text-center text-sm text-ink-muted">
                        {{ $incluirRascunhos
                            ? __('Digite um termo para encontrar artigos publicados e rascunhos.')
                            : __('Digite um termo para encontrar artigos publicados.') }}
                    </p>
                @else
                    <div class="mb-8 flex flex-wrap items-end justify-between gap-3 border-b border-ink/10 pb-6">
                        <p class="text-sm text-ink-muted">
                            {{ trans_choice(
                                ':count resultado para “:termo”|:count resultados para “:termo”',
                                $artigos->total(),
                                ['count' => $artigos->total(), 'termo' => $termo]
                            ) }}
                            @if ($incluirRascunhos)
                                <span class="text-ink/40">· {{ __('incluindo rascunhos') }}</span>
                            @endif
                        </p>
                    </div>

                    <div class="grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($artigos as $artigo)
                            <x-site.artigo-card :artigo="$artigo" />
                        @empty
                            <p class="col-span-full border border-dashed border-ink/15 px-6 py-16 text-center text-sm text-ink-muted">
                                {{ __('Nenhum artigo encontrado para “:termo”.', ['termo' => $termo]) }}
                            </p>
                        @endforelse
                    </div>

                    <div class="mt-12">
                        {{ $artigos->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
