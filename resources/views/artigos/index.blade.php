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

            <form method="GET" action="{{ route('artigos.index') }}" class="flex gap-3">
                <x-text-input
                    name="q"
                    type="search"
                    class="block w-full max-w-md"
                    :value="$busca"
                    placeholder="{{ __('Buscar por título ou identificador...') }}"
                />
                <x-primary-button type="submit">{{ __('Buscar') }}</x-primary-button>
            </form>

            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Título') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Categoria') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Autores') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-left font-medium text-gray-500">{{ __('Atualizado') }}</th>
                                <th class="px-4 py-3 text-right font-medium text-gray-500">{{ __('Ações') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($artigos as $artigo)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $artigo->titulo }}</div>
                                        <div class="text-xs text-gray-400">{{ $artigo->identificador }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $artigo->categoria?->nome ?? '—' }}
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
                                        {{ $artigo->atualizado_em?->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-end gap-3">
                                            @can('update', $artigo)
                                                <a href="{{ route('artigos.edit', $artigo) }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                                                    {{ __('Editar') }}
                                                </a>
                                            @endcan

                                            @can('delete', $artigo)
                                                <x-delete-button
                                                    :action="route('artigos.destroy', $artigo)"
                                                    :title="__('Excluir artigo?')"
                                                    :message="__('Tem certeza que deseja excluir “:nome”? Esta ação não pode ser desfeita.', ['nome' => $artigo->titulo])"
                                                    class="rounded-none p-0 font-medium text-red-600 hover:bg-transparent hover:text-red-500"
                                                >
                                                    {{ __('Excluir') }}
                                                </x-delete-button>
                                            @endcan
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
