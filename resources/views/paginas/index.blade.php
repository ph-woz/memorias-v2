<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
                {{ __('Páginas') }}
            </h2>

            @can('create', App\Models\Pagina::class)
                <a
                    href="{{ route('paginas.create') }}"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    {{ __('Nova página') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'pagina-excluida')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Página excluída com sucesso.') }}
                </div>
            @endif

            @if (session('status') === 'pagina-restaurada')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Página restaurada com sucesso.') }}
                </div>
            @endif

            <form method="GET" action="{{ route('paginas.index') }}" class="flex w-full items-center gap-3">
                <x-text-input
                    name="q"
                    type="search"
                    class="block min-w-0 flex-1"
                    :value="$busca"
                    placeholder="{{ __('Buscar por título ou URL...') }}"
                />
                <select
                    name="status"
                    class="block w-48 shrink-0 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="todos" @selected($status === 'todos')>{{ __('Status') }}</option>
                    <option value="ativos" @selected($status === 'ativos')>{{ __('Ativas') }}</option>
                    <option value="desativados" @selected($status === 'desativados')>{{ __('Desativadas') }}</option>
                    <option value="excluidos" @selected($status === 'excluidos')>{{ __('Excluídas') }}</option>
                </select>
                <x-primary-button type="submit" class="shrink-0 !px-3" aria-label="{{ __('Buscar') }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35m1.6-5.4a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                    </svg>
                </x-primary-button>
            </form>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3 text-sm text-gray-500">
                    {{ trans_choice(':count resultado encontrado|:count resultados encontrados', $paginas->total(), ['count' => $paginas->total()]) }}
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Título') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('URL') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Atualizado') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Ações') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($paginas as $pagina)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ $pagina->titulo }}
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-500">
                                        /{{ $pagina->slug }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-green-50 text-green-700' => $pagina->ativo,
                                            'bg-gray-100 text-gray-600' => ! $pagina->ativo,
                                        ])>
                                            {{ $pagina->ativo ? __('Ativa') : __('Inativa') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $pagina->updated_at?->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-0.5">
                                            @if ($excluidos)
                                                @can('restore', $pagina)
                                                    <x-restore-button
                                                        :action="route('paginas.restore', $pagina)"
                                                        :title="__('Restaurar página?')"
                                                        :message="__('Tem certeza que deseja restaurar “:nome”?', ['nome' => $pagina->titulo])"
                                                        aria-label="{{ __('Restaurar') }}"
                                                        title="{{ __('Restaurar') }}"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                                        </svg>
                                                    </x-restore-button>
                                                @endcan
                                            @else
                                                @if ($pagina->ativo)
                                                    <a
                                                        href="{{ $pagina->urlPublica() }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="inline-flex rounded-md p-1 text-gray-500 hover:bg-gray-50 hover:text-gray-700"
                                                        aria-label="{{ __('Ver no site') }}"
                                                        title="{{ __('Ver no site') }}"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                        </svg>
                                                    </a>
                                                @endif

                                                @can('update', $pagina)
                                                    <a
                                                        href="{{ route('paginas.edit', $pagina) }}"
                                                        class="inline-flex rounded-md p-1 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-500"
                                                        aria-label="{{ __('Editar') }}"
                                                        title="{{ __('Editar') }}"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                        </svg>
                                                    </a>
                                                @endcan

                                                @can('delete', $pagina)
                                                    <x-delete-button
                                                        :action="route('paginas.destroy', $pagina)"
                                                        :title="__('Excluir página?')"
                                                        :message="__('Tem certeza que deseja excluir “:nome”?', ['nome' => $pagina->titulo])"
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
                                        {{ __('Nenhuma página encontrada.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($paginas->hasPages())
                    <div class="border-t border-gray-100 px-4 py-3">
                        {{ $paginas->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
