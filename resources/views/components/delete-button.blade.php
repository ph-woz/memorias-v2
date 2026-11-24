@props([
    'action',
    'title',
    'message',
    'confirmLabel' => null,
])

@php
    $confirmLabel ??= __('Excluir');
@endphp

<button
    type="button"
    x-data
    x-on:click="$dispatch('confirm-delete', {
        action: @js($action),
        title: @js($title),
        message: @js($message),
        confirmLabel: @js($confirmLabel),
    })"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center rounded-md p-1 text-red-600 hover:bg-red-50 hover:text-red-500',
    ]) }}
>
    {{ $slot }}
</button>
