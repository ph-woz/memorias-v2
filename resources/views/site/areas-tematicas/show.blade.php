@extends('layouts.site')

@section('title', $areaTematica->nome.' — Memórias')

@section('content')
    <div class="mx-auto max-w-site px-5 pb-8 pt-28 sm:px-8 lg:px-10">
        <p class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-accent-deep">{{ __('Área temática') }}</p>
        <h1 class="mt-3 font-display text-display font-semibold tracking-tight text-ink">
            {{ $areaTematica->nome }}
        </h1>
        <p class="mt-5 max-w-2xl text-lg text-ink-muted">
            {{ __('Artigos ligados a este tema no acervo.') }}
        </p>

        @auth
            <a href="{{ route('areas_tematicas.edit', $areaTematica) }}" class="mt-4 inline-block text-sm font-medium text-accent-deep hover:text-ink">
                {{ __('Editar no painel') }}
            </a>
        @endauth

        <div class="mt-14 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($artigos as $artigo)
                <x-site.artigo-card :artigo="$artigo" />
            @empty
                <p class="col-span-full border border-dashed border-ink/15 px-6 py-16 text-center text-sm text-ink-muted">
                    {{ __('Nenhum artigo publicado nesta área temática.') }}
                </p>
            @endforelse
        </div>

        <div class="mt-12">
            {{ $artigos->links() }}
        </div>
    </div>
@endsection
