@props([
    'href',
    'active' => false,
    'icon' => 'dashboard',
])

@php
$classes = ($active ?? false)
    ? 'flex items-center gap-3 rounded-lg bg-indigo-50 px-3 py-2.5 text-sm font-medium text-indigo-700'
    : 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 hover:text-gray-900';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    <span class="shrink-0 text-current">
        @include('layouts.partials.sidebar-icons', ['icon' => $icon])
    </span>
    <span>{{ $slot }}</span>
</a>
