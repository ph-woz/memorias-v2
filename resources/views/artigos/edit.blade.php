<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
            {{ __('Editar artigo') }}
        </h2>
    </x-slot>

    <x-slot name="headerActions">
        <button
            type="submit"
            form="formulario-artigo"
            name="acao"
            value="ver_no_site"
            class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
            <span>{{ __('Ver no site') }}</span>
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
        </button>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            @if (session('status') === 'artigo-criado')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Artigo criado com sucesso.') }}
                </div>
            @endif

            @if (session('status') === 'artigo-importado')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Artigo importado do PDF. Revise as abas antes de publicar.') }}
                </div>
            @endif

            @if (session('status') === 'artigo-atualizado')
                <div class="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ __('Artigo atualizado com sucesso.') }}
                </div>
            @endif

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

    @if (session('abrir_site_url'))
        <script>
            window.open(@js(session('abrir_site_url')), '_blank', 'noopener,noreferrer');
        </script>
    @endif
</x-app-layout>
