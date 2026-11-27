<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
            {{ __('Editar área temática') }}
        </h2>
    </x-slot>

    <x-slot name="headerActions">
        <x-ver-no-site-button form="formulario-area-tematica" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-status-sucesso quando="area-tematica-criada" :mensagem="__('Área temática criada com sucesso.')" />
            <x-status-sucesso quando="area-tematica-atualizada" :mensagem="__('Área temática atualizada com sucesso.')" />

            <div class="rounded-lg bg-white p-6 shadow-sm sm:p-8">
                <form
                    id="formulario-area-tematica"
                    method="POST"
                    action="{{ route('areas_tematicas.update', $areaTematica) }}"
                    class="space-y-6"
                >
                    @csrf
                    @method('PUT')

                    @include('areas-tematicas.partials.form', ['areaTematica' => $areaTematica])

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <a href="{{ route('areas_tematicas.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Voltar') }}
                        </a>
                        <x-primary-button>{{ __('Atualizar área temática') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-abrir-site-apos-salvar />
</x-app-layout>
