@props([
    'name',
    'rotulo',
    'opcoes',
    'selecionados' => [],
    'largura' => 'w-52',
])

@php
    $estadoInicial = [
        'name' => $name,
        'rotulo' => $rotulo,
        'opcoes' => collect($opcoes)->map(static fn ($opcao): array => [
            'id' => (string) data_get($opcao, 'id'),
            'nome' => (string) data_get($opcao, 'nome'),
        ])->values()->all(),
        'selecionados' => array_map('strval', (array) $selecionados),
    ];
@endphp

<div
    class="relative shrink-0 {{ $largura }}"
    x-data="filtroMultiSelect(@js($estadoInicial))"
    @click.outside="fechar()"
    @keydown.escape.window="fechar()"
>
    <button
        type="button"
        class="flex h-10 w-full items-center justify-between gap-2 rounded-md border border-gray-300 bg-white px-3 text-left text-sm text-gray-700 shadow-sm transition hover:border-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        @click="alternarPainel()"
        :aria-expanded="aberto.toString()"
    >
        <span class="min-w-0 truncate" :class="selecionados.length > 0 ? 'font-medium text-gray-900' : 'text-gray-500'" x-text="rotuloBotao"></span>
        <svg class="h-4 w-4 shrink-0 text-gray-400 transition" :class="aberto ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <template x-for="id in selecionados" :key="id">
        <input type="hidden" :name="name" :value="id">
    </template>

    <div
        x-show="aberto"
        x-cloak
        x-transition.opacity.duration.100ms
        class="absolute left-0 z-30 mt-1 w-72 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg"
    >
        <div class="border-b border-gray-100 p-2">
            <input
                type="search"
                x-ref="buscaInput"
                x-model="busca"
                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="{{ __('Buscar…') }}"
                autocomplete="off"
            >
        </div>

        <ul class="max-h-56 overflow-auto py-1" role="listbox">
            <template x-for="opcao in opcoesFiltradas" :key="opcao.id">
                <li>
                    <button
                        type="button"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm transition"
                        :class="estaSelecionado(opcao.id) ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-50'"
                        @click="alternar(opcao.id)"
                        role="option"
                        :aria-selected="estaSelecionado(opcao.id).toString()"
                    >
                        <span
                            class="flex h-4 w-4 shrink-0 items-center justify-center rounded border"
                            :class="estaSelecionado(opcao.id) ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white'"
                        >
                            <svg x-show="estaSelecionado(opcao.id)" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </span>
                        <span class="min-w-0 truncate" x-text="opcao.nome"></span>
                    </button>
                </li>
            </template>

            <li x-show="opcoesFiltradas.length === 0" class="px-3 py-3 text-sm text-gray-500">
                {{ __('Nenhuma opção encontrada.') }}
            </li>
        </ul>

        <div class="flex items-center justify-between border-t border-gray-100 px-3 py-2">
            <button
                type="button"
                class="text-xs font-medium text-gray-500 hover:text-gray-800 disabled:opacity-40"
                @click="limpar()"
                :disabled="selecionados.length === 0"
            >
                {{ __('Limpar') }}
            </button>
            <button
                type="button"
                class="rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-500"
                @click="fechar()"
            >
                {{ __('Pronto') }}
            </button>
        </div>
    </div>
</div>
