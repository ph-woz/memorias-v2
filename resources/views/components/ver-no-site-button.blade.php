@props([
    'form',
])

<button
    type="submit"
    form="{{ $form }}"
    name="acao"
    value="ver_no_site"
    onclick="window.open('about:blank', 'memorias_ver_no_site')"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
    ]) }}
>
    <span>{{ __('Ver no site') }}</span>
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
    </svg>
</button>
