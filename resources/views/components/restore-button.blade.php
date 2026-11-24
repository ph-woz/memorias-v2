@props([
    'action',
    'title',
    'message',
    'confirmLabel' => null,
])

@php
    $confirmLabel ??= __('Restaurar');
@endphp

<button
    type="button"
    x-data
    x-on:click="$dispatch('confirm-restore', {
        action: @js($action),
        title: @js($title),
        message: @js($message),
        confirmLabel: @js($confirmLabel),
    })"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center rounded-md p-1 text-emerald-600 hover:bg-emerald-50 hover:text-emerald-500',
    ]) }}
>
    {{ $slot }}
</button>
