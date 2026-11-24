<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
            {{ __('Editar palavra-chave') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'palavra-chave-criada')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Palavra-chave criada com sucesso.') }}
                </div>
            @endif

            @if (session('status') === 'palavra-chave-atualizada')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Palavra-chave atualizada com sucesso.') }}
                </div>
            @endif

            <div class="rounded-lg bg-white p-6 shadow-sm sm:p-8">
                <form method="POST" action="{{ route('palavras_chave.update', $palavraChave) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('palavras-chave.partials.form', ['palavraChave' => $palavraChave])

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('palavras_chave.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Voltar') }}
                        </a>
                        <x-primary-button>{{ __('Atualizar palavra-chave') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
