<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
                {{ __('Artigos') }}
            </h2>

            @can('create', App\Models\Artigo::class)
                <a
                    href="{{ route('artigos.create') }}"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    {{ __('Novo artigo') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'artigo-excluido')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Artigo excluído com sucesso.') }}
                </div>
            @endif

            @if (session('status') === 'artigo-restaurado')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Artigo restaurado com sucesso.') }}
                </div>
            @endif

            @if ($atencao)
                @php
                    $rotuloAtencao = match ($atencao) {
                        'sem_foto' => __('Artigos sem foto'),
                        'sem_categoria' => __('Artigos sem categoria'),
                        'rascunhos_antigos' => __('Rascunhos parados (+14 dias)'),
                        'excluidos' => __('Artigos na lixeira'),
                        default => __('Filtro de atenção'),
                    };
                @endphp
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    <p>
                        <span class="font-semibold">{{ __('Precisa de atenção') }}:</span>
                        {{ $rotuloAtencao }}
                    </p>
                    <a href="{{ route('artigos.index') }}" class="font-medium text-amber-800 underline hover:text-amber-950">
                        {{ __('Limpar filtro') }}
                    </a>
                </div>
            @endif

            <form method="GET" action="{{ route('artigos.index') }}" class="space-y-3">
                @if ($atencao)
                    <input type="hidden" name="atencao" value="{{ $atencao }}">
                @endif
                <div class="flex w-full flex-wrap items-center gap-3">
                    <x-text-input
                        name="q"
                        type="search"
                        class="block min-w-0 flex-1"
                        :value="$busca"
                        placeholder="{{ __('Buscar por título ou slug...') }}"
                    />
                    <x-filtro-multi-select
                        name="categorias[]"
                        :rotulo="__('Categorias')"
                        :opcoes="$categoriasFiltro"
                        :selecionados="$categoriaIds"
                        largura="w-52"
                    />
                    <x-filtro-multi-select
                        name="areas_tematicas[]"
                        :rotulo="__('Áreas temáticas')"
                        :opcoes="$areasTematicasFiltro"
                        :selecionados="$areaTematicaIds"
                        largura="w-56"
                    />
                    <select
                        name="status"
                        class="block w-44 shrink-0 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="todos" @selected($status === 'todos')>{{ __('Status') }}</option>
                        <option value="publicados" @selected($status === 'publicados')>{{ __('Publicados') }}</option>
                        <option value="rascunhos" @selected($status === 'rascunhos')>{{ __('Rascunhos') }}</option>
                        <option value="excluidos" @selected($status === 'excluidos')>{{ __('Excluídos') }}</option>
                    </select>
                    <select
                        name="ordenar"
                        class="block w-52 shrink-0 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="atualizado_desc" @selected($ordenacao === 'atualizado_desc')>{{ __('Mais atualizados') }}</option>
                        <option value="atualizado_asc" @selected($ordenacao === 'atualizado_asc')>{{ __('Menos atualizados') }}</option>
                        <option value="titulo_asc" @selected($ordenacao === 'titulo_asc')>{{ __('Título A–Z') }}</option>
                        <option value="titulo_desc" @selected($ordenacao === 'titulo_desc')>{{ __('Título Z–A') }}</option>
                        <option value="publicado_desc" @selected($ordenacao === 'publicado_desc')>{{ __('Publicação recente') }}</option>
                        <option value="publicado_asc" @selected($ordenacao === 'publicado_asc')>{{ __('Publicação antiga') }}</option>
                    </select>
                    <x-primary-button type="submit" class="shrink-0 !px-3" aria-label="{{ __('Buscar') }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35m1.6-5.4a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                        </svg>
                    </x-primary-button>
                </div>

                @if ($etiquetasFiltro->isNotEmpty())
                    <div class="rounded-md border border-gray-200 bg-white px-3 py-2">
                        <p class="mb-2 text-xs font-medium text-gray-500">{{ __('Etiquetas') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($etiquetasFiltro as $etiquetaFiltro)
                                <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        name="etiquetas[]"
                                        value="{{ $etiquetaFiltro->id }}"
                                        class="h-3.5 w-3.5 shrink-0 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        @checked(in_array($etiquetaFiltro->id, $etiquetaIds, true))
                                    >
                                    <span
                                        class="h-2.5 w-2.5 shrink-0 rounded-full"
                                        style="background-color: {{ $etiquetaFiltro->cor }}"
                                        aria-hidden="true"
                                    ></span>
                                    <span>{{ $etiquetaFiltro->nome }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </form>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3 text-sm text-gray-500">
                    {{ trans_choice(':count resultado encontrado|:count resultados encontrados', $artigos->total(), ['count' => $artigos->total()]) }}
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Artigo') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Categoria') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Autores') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">
                                    {{ $excluidos ? __('Excluído por') : __('Atualizado') }}
                                </th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Ações') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($artigos as $artigo)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($artigo->url_foto)
                                                <img
                                                    src="{{ $artigo->url_foto }}"
                                                    alt=""
                                                    class="h-10 w-10 shrink-0 rounded-lg object-cover"
                                                >
                                            @else
                                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-xs text-gray-400">
                                                    —
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="truncate font-medium text-gray-900">{{ $artigo->titulo }}</p>
                                                <p class="truncate font-mono text-xs text-gray-500">{{ $artigo->slug }}</p>
                                                @if ($artigo->etiquetas->isNotEmpty())
                                                    <div class="mt-1 flex flex-wrap gap-1">
                                                        @foreach ($artigo->etiquetas as $etiqueta)
                                                            <span
                                                                class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold text-white"
                                                                style="background-color: {{ $etiqueta->cor }}"
                                                            >
                                                                {{ $etiqueta->nome }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $artigo->categorias->pluck('nome')->join(', ') ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $artigo->autores->pluck('nome')->join(', ') ?: '—' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-green-50 text-green-700' => $artigo->estaPublicado(),
                                            'bg-gray-100 text-gray-600' => ! $artigo->estaPublicado(),
                                        ])>
                                            {{ $artigo->status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        @if ($excluidos)
                                            <div>{{ $artigo->deletadoPor?->nome ?? '—' }}</div>
                                            <div class="text-xs text-gray-400">{{ $artigo->deleted_at?->format('d/m/Y H:i') }}</div>
                                        @else
                                            {{ $artigo->updated_at?->format('d/m/Y H:i') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-0.5">
                                            @if ($excluidos)
                                                @can('restore', $artigo)
                                                    <x-restore-button
                                                        :action="route('artigos.restore', $artigo)"
                                                        :title="__('Restaurar artigo?')"
                                                        :message="__('Tem certeza que deseja restaurar “:nome”?', ['nome' => $artigo->titulo])"
                                                        aria-label="{{ __('Restaurar') }}"
                                                        title="{{ __('Restaurar') }}"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                                        </svg>
                                                    </x-restore-button>
                                                @endcan
                                            @else
                                                <a
                                                    href="{{ route('site.artigos.show', $artigo) }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="inline-flex rounded-md p-1 text-gray-500 hover:bg-gray-50 hover:text-gray-700"
                                                    aria-label="{{ __('Abrir no site') }}"
                                                    title="{{ __('Abrir no site') }}"
                                                >
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                                    </svg>
                                                </a>

                                                @can('update', $artigo)
                                                    <a
                                                        href="{{ route('artigos.edit', $artigo) }}"
                                                        class="inline-flex rounded-md p-1 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-500"
                                                        aria-label="{{ __('Editar') }}"
                                                        title="{{ __('Editar') }}"
                                                    >
                                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                        </svg>
                                                    </a>
                                                @endcan

                                                @can('delete', $artigo)
                                                    <x-delete-button
                                                        :action="route('artigos.destroy', $artigo)"
                                                        :title="__('Excluir artigo?')"
                                                        :message="__('Tem certeza que deseja excluir “:nome”?', ['nome' => $artigo->titulo])"
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
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        {{ __('Nenhum artigo encontrado.') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($artigos->hasPages())
                    <div class="border-t border-gray-100 px-4 py-3">
                        {{ $artigos->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
