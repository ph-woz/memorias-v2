@extends('layouts.site')

@section('title', ($categoria->seo_titulo ?: $categoria->nome).' — Memórias')

@section('meta')
    @if ($categoria->seo_descricao)
        <meta name="description" content="{{ $categoria->seo_descricao }}">
    @endif
@endsection

@section('content')
    <div class="pt-24">
        @if ($categoria->url_foto)
            <div class="relative mb-12 aspect-[21/8] w-full overflow-hidden bg-ink">
                <img
                    src="{{ $categoria->url_foto }}"
                    alt=""
                    class="h-full w-full object-cover opacity-80"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-ink/70 to-transparent"></div>
            </div>
        @endif

        <div class="mx-auto max-w-site px-5 sm:px-8 lg:px-10">
            <p class="text-[0.7rem] font-semibold uppercase tracking-[0.2em] text-accent-deep">{{ __('Categoria') }}</p>
            <h1 class="mt-3 font-display text-display font-semibold tracking-tight text-ink">
                {{ $categoria->nome }}
            </h1>

            @if ($categoria->seo_descricao)
                <p class="mt-5 max-w-2xl text-lg text-ink-muted">{{ $categoria->seo_descricao }}</p>
            @endif

            @auth
                <a href="{{ route('categorias.edit', $categoria) }}" class="mt-4 inline-block text-sm font-medium text-accent-deep hover:text-ink">
                    {{ __('Editar no painel') }}
                </a>
            @endauth

            <div class="mt-14 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($artigos as $artigo)
                    <x-site.artigo-card :artigo="$artigo" />
                @empty
                    <p class="col-span-full border border-dashed border-ink/15 px-6 py-16 text-center text-sm text-ink-muted">
                        {{ __('Nenhum artigo publicado nesta categoria.') }}
                    </p>
                @endforelse
            </div>

            <div class="mt-12">
                {{ $artigos->links() }}
            </div>
        </div>
    </div>
@endsection
