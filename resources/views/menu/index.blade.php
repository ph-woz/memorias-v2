<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
                {{ __('Menu') }}
            </h2>

            @can('create', App\Models\MenuItem::class)
                <a
                    href="{{ route('menu.create') }}"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    {{ __('Novo item') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'menu-item-excluido')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Item de menu excluído com sucesso.') }}
                </div>
            @endif

            @if (session('status') === 'menu-item-restaurado')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Item de menu restaurado com sucesso.') }}
                </div>
            @endif

            <form method="GET" action="{{ route('menu.index') }}" class="flex w-full items-center gap-3">
                <x-text-input
                    name="q"
                    type="search"
                    class="block min-w-0 flex-1"
                    :value="$busca"
                    placeholder="{{ __('Buscar por texto ou URL...') }}"
                />
                <select
                    name="status"
                    class="block w-48 shrink-0 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="todos" @selected($status === 'todos')>{{ __('Status') }}</option>
                    <option value="ativos" @selected($status === 'ativos')>{{ __('Ativos') }}</option>
                    <option value="desativados" @selected($status === 'desativados')>{{ __('Desativados') }}</option>
                    <option value="excluidos" @selected($status === 'excluidos')>{{ __('Excluídos') }}</option>
                </select>
                <x-primary-button type="submit" class="shrink-0 !px-3" aria-label="{{ __('Buscar') }}">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m21 21-4.35-4.35m1.6-5.4a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                    </svg>
                </x-primary-button>
            </form>

            <div
                class="overflow-hidden rounded-lg bg-white shadow-sm"
                @unless ($excluidos)
                    x-data="bannerOrdenacao(@js($itens->pluck('id')->values()), '{{ route('menu.reorder') }}')"
                @endunless
            >
                <div class="border-b border-gray-100 px-4 py-3 text-sm text-gray-500">
                    {{ trans_choice(':count item|:count itens', $itens->count(), ['count' => $itens->count()]) }}
                    @unless ($excluidos)
                        <span x-show="salvando" class="ml-2 text-indigo-600" style="display: none;">{{ __('Salvando...') }}</span>
                        <span x-show="salvo" class="ml-2 text-green-600" style="display: none;">{{ __('Ordem salva') }}</span>
                        <span x-show="erro" class="ml-2 text-red-600" x-text="erro" style="display: none;"></span>
                    @endunless
                </div>

                <ul class="divide-y divide-gray-100" @unless ($excluidos) x-ref="lista" @endunless>
                    @forelse ($itens as $item)
                        <li
                            data-id="{{ $item->id }}"
                            @unless ($excluidos)
                                draggable="true"
                                @dragstart="iniciarArraste($event)"
                                @dragover.prevent="arrastarSobre($event)"
                                @drop.prevent="soltar($event)"
                                @dragend="finalizarArraste()"
                            @endunless
                            @class([
                                'px-4 py-4 transition',
                                'cursor-grab active:cursor-grabbing' => ! $excluidos,
                            ])
                            @unless ($excluidos)
                                :class="{ 'bg-indigo-50/60': arrastandoId === {{ $item->id }} }"
                            @endunless
                        >
                            <div class="flex items-center gap-4">
                                @unless ($excluidos)
                                    <div class="flex h-10 w-8 shrink-0 items-center justify-center text-gray-300" aria-hidden="true">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 9h16.5m-16.5 6.75h16.5" />
                                        </svg>
                                    </div>
                                @endunless

                                <div class="min-w-0 flex-1">
                                    <div class="truncate font-medium text-gray-900">{{ $item->rotulo }}</div>
                                    <div class="mt-1 flex flex-wrap gap-2 text-xs">
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-0.5 font-medium',
                                            'bg-green-50 text-green-700' => $item->ativo,
                                            'bg-gray-100 text-gray-600' => ! $item->ativo,
                                        ])>
                                            {{ $item->ativo ? __('Ativo') : __('Inativo') }}
                                        </span>
                                        @if ($item->temSublinks())
                                            <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 font-medium text-indigo-700">
                                                {{ trans_choice(':count sublink|:count sublinks', $item->filhos->count(), ['count' => $item->filhos->count()]) }}
                                            </span>
                                        @endif
                                        @if ($item->target_blank)
                                            <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 font-medium text-amber-700">
                                                {{ __('Nova aba') }}
                                            </span>
                                        @endif
                                        @if ($item->url)
                                            <span class="truncate font-mono text-gray-500">{{ $item->url }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex shrink-0 items-center gap-0.5">
                                    @if ($excluidos)
                                        @can('restore', $item)
                                            <x-restore-button
                                                :action="route('menu.restore', $item)"
                                                :title="__('Restaurar item?')"
                                                :message="__('Tem certeza que deseja restaurar “:nome”?', ['nome' => $item->rotulo])"
                                                aria-label="{{ __('Restaurar') }}"
                                                title="{{ __('Restaurar') }}"
                                            >
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                                </svg>
                                            </x-restore-button>
                                        @endcan
                                    @else
                                        @can('update', $item)
                                            <a
                                                href="{{ route('menu.edit', $item) }}"
                                                class="inline-flex rounded-md p-1 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-500"
                                                aria-label="{{ __('Editar') }}"
                                                title="{{ __('Editar') }}"
                                            >
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                                </svg>
                                            </a>
                                        @endcan

                                        @can('delete', $item)
                                            <x-delete-button
                                                :action="route('menu.destroy', $item)"
                                                :title="__('Excluir item?')"
                                                :message="__('Tem certeza que deseja excluir “:nome”?', ['nome' => $item->rotulo])"
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
                            </div>

                            @if ($item->filhos->isNotEmpty())
                                <ul class="mt-3 space-y-2 border-l border-gray-200 pl-6">
                                    @foreach ($item->filhos as $filho)
                                        <li class="rounded-md bg-gray-50 px-3 py-2">
                                            <div class="truncate text-sm font-medium text-gray-800">{{ $filho->rotulo }}</div>
                                            <div class="mt-0.5 flex flex-wrap gap-2 text-xs text-gray-500">
                                                @if ($filho->target_blank)
                                                    <span>{{ __('Nova aba') }}</span>
                                                @endif
                                                @if ($filho->url)
                                                    <span class="font-mono">{{ $filho->url }}</span>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @empty
                        <li class="px-4 py-10 text-center text-sm text-gray-500">
                            {{ __('Nenhum item de menu encontrado.') }}
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
