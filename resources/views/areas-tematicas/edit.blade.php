<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
            {{ __('Editar área temática') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'area-tematica-criada')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Área temática criada com sucesso.') }}
                </div>
            @endif

            @if (session('status') === 'area-tematica-atualizada')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Área temática atualizada com sucesso.') }}
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm sm:p-8">
                <form method="POST" action="{{ route('areas_tematicas.update', $areaTematica) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('areas-tematicas.partials.form', ['areaTematica' => $areaTematica])

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('areas_tematicas.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Voltar') }}
                        </a>
                        <x-primary-button>{{ __('Atualizar área temática') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
