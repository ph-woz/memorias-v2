@props([
    'size' => 'md',
])

@php
    $marca = 'Memórias';
    $subtitulo = 'Instituto Oswaldo Cruz';

    $tituloClass = match ($size) {
        'lg' => 'text-3xl sm:text-4xl',
        'sm', 'xs' => 'text-lg',
        default => 'text-xl',
    };

    $subtituloClass = match ($size) {
        'lg' => 'mt-1 text-xs tracking-[0.22em]',
        'sm', 'xs' => 'mt-0.5 text-[11px] tracking-[0.16em]',
        default => 'mt-0.5 text-xs tracking-[0.2em]',
    };
@endphp

<span {{ $attributes->class('flex max-w-full flex-col leading-tight') }}>
    <span @class(['font-display font-semibold tracking-tight text-ink', $tituloClass])>
        {{ $marca }}
    </span>
    <span @class(['font-medium uppercase text-ink-muted', $subtituloClass])>
        {{ $subtitulo }}
    </span>
</span>
