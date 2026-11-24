@extends('layouts.site')

@section('title', ($pagina->seo_titulo ?: $pagina->titulo).' — Memórias')

@section('meta')
    @if ($pagina->seo_descricao)
        <meta name="description" content="{{ $pagina->seo_descricao }}">
    @endif
    @if ($pagina->seo_palavras_chave)
        <meta name="keywords" content="{{ $pagina->seo_palavras_chave }}">
    @endif
    <meta property="og:title" content="{{ $pagina->seo_titulo ?: $pagina->titulo }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $pagina->urlPublica() }}">
@endsection

@section('content')
    <article class="pt-28 pb-20">
        <header class="mx-auto max-w-3xl px-5 sm:px-8">
            <h1 class="font-display text-display font-semibold text-ink">
                {{ $pagina->titulo }}
            </h1>

            @auth
                <div class="mt-4">
                    <a href="{{ route('paginas.edit', $pagina) }}" class="text-sm font-medium text-accent-deep hover:text-ink">
                        {{ __('Editar no painel') }}
                    </a>
                </div>
            @endauth
        </header>

        <div class="mx-auto mt-12 max-w-3xl px-5 sm:px-8">
            <div class="conteudo-rico prose prose-stone max-w-none prose-headings:font-display prose-headings:tracking-tight prose-a:text-accent-deep">
                {!! $pagina->conteudo !!}
            </div>
        </div>
    </article>
@endsection
