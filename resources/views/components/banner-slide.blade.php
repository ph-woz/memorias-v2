@props([
    'titulo' => '',
    'slogan' => '',
    'imagemUrl' => null,
    'overlayCor' => '#000000',
    'overlayOpacidade' => 45,
    'posicaoHorizontal' => \App\Enums\PosicaoHorizontal::Esquerda,
    'posicaoVertical' => \App\Enums\PosicaoVertical::Baixo,
    'ctaTexto' => '',
    'ctaLink' => '',
    'compact' => false,
])

@php
    use App\Enums\PosicaoHorizontal;
    use App\Enums\PosicaoVertical;

    $altura = $compact ? 'min-h-[280px] sm:min-h-[340px]' : 'min-h-[420px] sm:min-h-[560px] lg:min-h-[640px]';
    $horizontal = $posicaoHorizontal instanceof PosicaoHorizontal
        ? $posicaoHorizontal
        : PosicaoHorizontal::from($posicaoHorizontal);
    $vertical = $posicaoVertical instanceof PosicaoVertical
        ? $posicaoVertical
        : PosicaoVertical::from($posicaoVertical);
    $opacidade = max(0, min(100, (int) $overlayOpacidade)) / 100;
@endphp

<section
    {{ $attributes->merge(['class' => "relative overflow-hidden {$altura} bg-stone-900"]) }}
>
    @if ($imagemUrl)
        <img
            src="{{ $imagemUrl }}"
            alt="{{ $titulo }}"
            class="absolute inset-0 h-full w-full object-cover"
        >
    @endif

    @if ($opacidade > 0)
        <div
            class="absolute inset-0"
            style="background-color: {{ $overlayCor }}; opacity: {{ $opacidade }};"
        ></div>
    @endif

    <div class="relative z-10 flex h-full w-full {{ $vertical->classeItems() }} {{ $horizontal->classeJustify() }}">
        <div class="w-full max-w-3xl px-6 py-10 sm:px-10 sm:py-14 lg:px-16 {{ $horizontal->classeTexto() }}">
            <div class="text-white">
                @if ($titulo !== '')
                    <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl lg:text-5xl">
                        {{ $titulo }}
                    </h2>
                @endif

                @if ($slogan !== '')
                    <p class="mt-3 max-w-2xl text-base text-white/85 sm:text-lg {{ $horizontal === PosicaoHorizontal::Centro ? 'mx-auto' : ($horizontal === PosicaoHorizontal::Direita ? 'ml-auto' : '') }}">
                        {{ $slogan }}
                    </p>
                @endif

                @if ($ctaTexto !== '' && $ctaLink !== '')
                    <div class="mt-6">
                        <a
                            href="{{ $ctaLink }}"
                            class="inline-flex items-center rounded-md bg-white px-5 py-2.5 text-sm font-semibold text-stone-900 shadow-sm transition hover:bg-stone-100"
                        >
                            {{ $ctaTexto }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
