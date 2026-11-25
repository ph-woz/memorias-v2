<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
            {{ __('Novo artigo') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div
                class="rounded-lg border border-indigo-100 bg-indigo-50/60 p-6 shadow-sm sm:p-8"
                x-data="{
                    nomeArquivo: '',
                    tamanhoArquivo: '',
                    enviando: false,
                    atualizarArquivo(evento) {
                        const arquivo = evento.target.files?.[0] ?? null;
                        if (! arquivo || this.enviando) {
                            if (! arquivo) {
                                this.nomeArquivo = '';
                                this.tamanhoArquivo = '';
                            }
                            return;
                        }

                        this.nomeArquivo = arquivo.name;
                        const mb = arquivo.size / (1024 * 1024);
                        this.tamanhoArquivo = mb >= 1
                            ? mb.toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + ' MB'
                            : Math.max(1, Math.round(arquivo.size / 1024)) + ' KB';
                        this.enviando = true;
                        this.$nextTick(() => this.$refs.formularioImportacaoPdf.submit());
                    },
                }"
            >
                <h3 class="text-base font-semibold text-gray-900">{{ __('Importar de PDF com IA') }}</h3>

                <form
                    x-ref="formularioImportacaoPdf"
                    method="POST"
                    action="{{ route('artigos.importar-pdf') }}"
                    enctype="multipart/form-data"
                    class="mt-5 space-y-3"
                >
                    @csrf

                    <div>
                        <input
                            id="pdf"
                            name="pdf"
                            type="file"
                            accept="application/pdf,.pdf"
                            required
                            class="sr-only"
                            @change="atualizarArquivo($event)"
                        >
                        {{-- Não usar :disabled no input: campos disabled não entram no submit e geram "PDF field is required". --}}
                        <label
                            for="pdf"
                            class="mt-4 flex items-center gap-4 rounded-lg border border-dashed border-indigo-200 bg-white/80 px-4 py-4 transition"
                            :class="enviando ? 'pointer-events-none cursor-wait opacity-80' : 'cursor-pointer hover:border-indigo-400 hover:bg-white'"
                            @click="if (enviando) { $event.preventDefault() }"
                        >
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-indigo-600 text-white">
                                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M4.75 2A1.75 1.75 0 0 0 3 3.75v12.5c0 .966.784 1.75 1.75 1.75h10.5A1.75 1.75 0 0 0 17 16.25V8.414a1.75 1.75 0 0 0-.513-1.238L11.824 2.513A1.75 1.75 0 0 0 10.586 2H4.75Z" />
                                    <path d="M11 2.75V6.5a.75.75 0 0 0 .75.75h3.75" class="opacity-40" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-gray-900">
                                    <span x-show="!nomeArquivo">{{ __('Selecionar arquivo PDF') }}</span>
                                    <span x-show="nomeArquivo" x-cloak x-text="nomeArquivo" class="truncate"></span>
                                </span>
                                <span class="mt-0.5 block text-xs text-gray-500">
                                    <span x-show="!nomeArquivo && !enviando">{{ __('Nenhum arquivo selecionado · PDF de até 20 MB · a importação inicia automaticamente') }}</span>
                                    <span x-show="nomeArquivo && !enviando" x-cloak>
                                        <span x-text="tamanhoArquivo"></span> · {{ __('Iniciando importação…') }}
                                    </span>
                                    <span x-show="enviando" x-cloak>
                                        <span x-text="tamanhoArquivo"></span> · {{ __('Enviando arquivo…') }}
                                    </span>
                                </span>
                            </span>
                            <span
                                class="shrink-0 rounded-md px-3 py-1.5 text-xs font-semibold ring-1 ring-inset"
                                :class="enviando ? 'bg-indigo-100 text-indigo-800 ring-indigo-200' : 'bg-indigo-50 text-indigo-700 ring-indigo-100'"
                                x-text="enviando ? '{{ __('Enviando…') }}' : '{{ __('Escolher') }}'"
                            ></span>
                        </label>
                        <x-input-error class="mt-2" :messages="$errors->get('pdf')" />
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
