<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
            {{ __('Editar item de menu') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'menu-item-criado')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Item de menu criado com sucesso.') }}
                </div>
            @endif

            @if (session('status') === 'menu-item-atualizado')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Item de menu atualizado com sucesso.') }}
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm sm:p-8">
                <form method="POST" action="{{ route('menu.update', $menuItem) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('menu.partials.form', ['menuItem' => $menuItem])

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('menu.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Voltar') }}
                        </a>
                        <x-primary-button>{{ __('Salvar alterações') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
