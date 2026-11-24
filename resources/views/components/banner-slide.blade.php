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

    $altura = $compact ? 'min-h-[280px] sm:min-h-[340px]' : 'min-h-[100svh]';
    $opacidade = max(0, min(100, (int) $overlayOpacidade)) / 100;
@endphp

<section {{ $attributes->merge(['class' => "relative overflow-hidden {$altura} bg-ink"]) }}>
    @if ($imagemUrl)
        <img
            src="{{ $imagemUrl }}"
            alt="{{ $titulo }}"
            class="banner-media absolute inset-0 h-full w-full scale-105 object-cover"
        >
    @endif

    <div
        class="absolute inset-0"
        style="background: linear-gradient(180deg, rgba(7,21,18,0.45) 0%, rgba(7,21,18,0.2) 35%, rgba(7,21,18,0.75) 100%);"
    ></div>

    @if ($opacidade > 0)
        <div
            class="absolute inset-0"
            style="background-color: {{ $overlayCor }}; opacity: {{ $opacidade * 0.35 }};"
        ></div>
    @endif

    {{-- Composição hero: marca + 1 headline + 1 frase + CTA — sempre ancorada no rodapé da viewport --}}
    <div class="relative z-10 flex h-full min-h-[inherit] w-full items-end">
        <div class="w-full px-5 pb-28 pt-32 sm:px-8 sm:pb-32 lg:px-10">
            <div class="mx-auto max-w-site text-white">
                <p class="font-display text-[clamp(3rem,8vw,6.5rem)] font-semibold leading-[0.95] tracking-tight">
                    Memórias
                </p>

                @if ($titulo !== '')
                    <h2 class="mt-6 max-w-2xl font-display text-2xl font-medium leading-snug text-white/95 sm:text-3xl lg:text-4xl">
                        {{ $titulo }}
                    </h2>
                @endif

                @if ($slogan !== '')
                    <p class="mt-4 max-w-lg text-base leading-relaxed text-white/70 sm:text-lg">
                        {{ $slogan }}
                    </p>
                @endif

                @if ($ctaTexto !== '' && $ctaLink !== '')
                    <div class="mt-8">
                        <a
                            href="{{ $ctaLink }}"
                            class="inline-flex items-center gap-3 bg-white px-6 py-3.5 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-ink transition hover:bg-accent-bright"
                        >
                            {{ $ctaTexto }}
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
