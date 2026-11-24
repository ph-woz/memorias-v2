<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
                {{ __('Editar página') }}
            </h2>
            @if ($pagina->ativo)
                <a
                    href="{{ $pagina->urlPublica() }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                >
                    {{ __('Ver no site') }}
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'pagina-criada')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Página criada com sucesso.') }}
                </div>
            @endif

            @if (session('status') === 'pagina-atualizada')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Página atualizada com sucesso.') }}
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm sm:p-8">
                <form method="POST" action="{{ route('paginas.update', $pagina) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('paginas.partials.form', ['pagina' => $pagina])

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('paginas.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Voltar') }}
                        </a>
                        <x-primary-button>{{ __('Salvar alterações') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
