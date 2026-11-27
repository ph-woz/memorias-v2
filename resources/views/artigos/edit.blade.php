<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
            {{ __('Editar artigo') }}
        </h2>
    </x-slot>

    <x-slot name="headerActions">
        <x-ver-no-site-button form="formulario-artigo" />
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-status-sucesso quando="artigo-criado" :mensagem="__('Artigo criado com sucesso.')" />
            <x-status-sucesso quando="artigo-importado" :mensagem="__('Artigo importado do PDF. Revise as abas antes de publicar.')" />
            <x-status-sucesso quando="artigo-atualizado" :mensagem="__('Artigo atualizado com sucesso.')" />

            <div class="rounded-lg bg-white p-6 shadow-sm sm:p-8">
                <form
                    id="formulario-artigo"
                    method="POST"
                    action="{{ route('artigos.update', $artigo) }}"
                    enctype="multipart/form-data"
                    class="space-y-6"
                >
                    @csrf
                    @method('PUT')

                    @include('artigos.partials.form', ['artigo' => $artigo])

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <a href="{{ route('artigos.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Voltar') }}
                        </a>
                        <x-primary-button>{{ __('Atualizar artigo') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <x-abrir-site-apos-salvar />
</x-app-layout>
