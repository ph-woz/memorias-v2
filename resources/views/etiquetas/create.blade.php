<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
            {{ __('Nova etiqueta') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg bg-white p-6 shadow-sm sm:p-8">
                <form method="POST" action="{{ route('etiquetas.store') }}" class="space-y-6">
                    @csrf

                    @include('etiquetas.partials.form')

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('etiquetas.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancelar') }}
                        </a>
                        <x-primary-button>{{ __('Salvar etiqueta') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
