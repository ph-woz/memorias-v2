<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
            {{ __('Novo artigo') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-lg border border-indigo-100 bg-indigo-50/60 p-6 shadow-sm sm:p-8">
                <h3 class="text-base font-semibold text-gray-900">{{ __('Importar de PDF') }}</h3>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('Envie o PDF do artigo. A IA localiza os campos e o sistema monta um rascunho com texto literal do PDF (sem resumir o conteúdo), para você revisar.') }}
                </p>

                <form method="POST" action="{{ route('artigos.importar-pdf') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf

                    <div>
                        <x-input-label for="pdf" :value="__('Arquivo PDF')" />
                        <input
                            id="pdf"
                            name="pdf"
                            type="file"
                            accept="application/pdf,.pdf"
                            required
                            class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-indigo-500"
                        >
                        <x-input-error class="mt-2" :messages="$errors->get('pdf')" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('Máximo 20 MB.') }}</p>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Importar PDF') }}</x-primary-button>
                    </div>
                </form>
            </div>

            <div class="rounded-lg bg-white p-6 shadow-sm sm:p-8">
                <form method="POST" action="{{ route('artigos.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    @include('artigos.partials.form')

                    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
                        <a href="{{ route('artigos.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancelar') }}
                        </a>
                        <x-primary-button>{{ __('Salvar artigo') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
