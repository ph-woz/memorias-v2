<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
            {{ __('Editar categoria') }}
        </h2>
    </x-slot>

    <x-slot name="headerActions">
        <x-ver-no-site-button form="formulario-categoria" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-status-sucesso quando="categoria-criada" :mensagem="__('Categoria criada com sucesso.')" />
            <x-status-sucesso quando="categoria-atualizada" :mensagem="__('Categoria atualizada com sucesso.')" />

            <div class="rounded-lg bg-white p-6 shadow-sm sm:p-8">
                <form
                    id="formulario-categoria"
                    method="POST"
                    action="{{ route('categorias.update', $categoria) }}"
                    enctype="multipart/form-data"
                    class="space-y-6"
                >
                    @csrf
                    @method('PUT')

                    @include('categorias.partials.form', ['categoria' => $categoria])

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <a href="{{ route('categorias.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Voltar') }}
                        </a>
                        <x-primary-button>{{ __('Atualizar categoria') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-abrir-site-apos-salvar />
</x-app-layout>
