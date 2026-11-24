<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
                {{ __('Palavras-chave') }}
            </h2>

            @can('create', App\Models\PalavraChave::class)
                <a
                    href="{{ route('palavras_chave.create') }}"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    {{ __('Nova palavra-chave') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'palavra-chave-excluida')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Palavra-chave excluída com sucesso.') }}
                </div>
            @endif

            @if (session('status') === 'palavra-chave-restaurada')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Palavra-chave restaurada com sucesso.') }}
                </div>
            @endif

            <form method="GET" action="{{ route('palavras_chave.index') }}" class="flex w-full items-center gap-3">
                <x-text-input
                    name="q"
                    type="search"
                    class="block min-w-0 flex-1"
                    :value="$busca"
                    placeholder="{{ __('Buscar por nome...') }}"
                />
                <select
                    name="status"
                    class="block w-48 shrink-0 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="todos" @selected($status === 'todos')>{{ __('Status') }}</option>
                    <option value="excluidos" @selected($status === 'excluidos')>{{ __('Excluídos') }}</option>
                </select>
                <x-primary-button type="submit" class="shrink-0 !px-3" aria-label="{{ __('Buscar') }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35m1.6-5.4a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                    </svg>
                </x-primary-button>
            </form>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3 text-sm text-gray-500">
                    {{ trans_choice(':count resultado encontrado|:count resultados encontrados', $palavrasChave->total(), ['count' => $palavrasChave->total()]) }}
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Nome') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Artigos') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Atualizado') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Ações') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($palavrasChave as $palavraChave)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $palavraChave->nome }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $palavraChave->artigos_count }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $palavraChave->updated_at?->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-0.5">
                                            @if ($excluidos)
                                                @can('restore', $palavraChave)
                                                    <x-restore-button
                                                        :action="route('palavras_chave.restore', $palavraChave)"
                                                        :title="__('Restaurar palavra-chave?')"
                                                        :message="__('Tem certeza que deseja restaurar “:nome”?', ['nome' => $palavraChave->nome])"
                                                        aria-label="{{ __('Restaurar') }}"
                                                        title="{{ __('Restaurar') }}"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                                        </svg>
                                                    </x-restore-button>
                                                @endcan
                                            @else
                                                @can('update', $palavraChave)
                                                    <a
                                                        href="{{ route('palavras_chave.edit', $palavraChave) }}"
                                                        class="inline-flex rounded-md p-1 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-500"
                                                        aria-label="{{ __('Editar') }}"
                                                        title="{{ __('Editar') }}"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                        </svg>
                                                    </a>
                                                @endcan

                                                @can('delete', $palavraChave)
                                                    <x-delete-button
                                                        :action="route('palavras_chave.destroy', $palavraChave)"
                                                        :title="__('Excluir palavra-chave?')"
                                                        :message="__('Tem certeza que deseja excluir “:nome”?', ['nome' => $palavraChave->nome])"
                                                        aria-label="{{ __('Excluir') }}"
                                                        title="{{ __('Excluir') }}"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                        </svg>
                                                    </x-delete-button>
                                                @endcan
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        {{ __('Nenhuma palavra-chave encontrada.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($palavrasChave->hasPages())
                    <div class="border-t border-gray-100 px-4 py-3">
                        {{ $palavrasChave->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
