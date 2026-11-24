<x-app-layout>
    <x-slot name="header">
        <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
            {{ __('Importando artigo do PDF') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div
            class="mx-auto max-w-2xl space-y-4 px-4 sm:px-6 lg:px-8"
            x-data="{
                statusUrl: @js(route('artigos.importacoes.status', $importacao)),
                status: @js($importacao->status->value),
                progresso: @js((int) $importacao->progresso),
                etapa: @js($importacao->etapa ?: __('Na fila...')),
                erro: @js($importacao->erro ?? ''),
                timer: null,
                get finalizada() {
                    return this.status === 'concluida' || this.status === 'falhou';
                },
                get falhou() {
                    return this.status === 'falhou';
                },
                get percentual() {
                    return Math.max(0, Math.min(100, Number(this.progresso) || 0));
                },
                iniciar() {
                    if (this.finalizada) {
                        return;
                    }
                    this.timer = setInterval(() => this.consultar(), 1500);
                },
                async consultar() {
                    try {
                        const resposta = await fetch(this.statusUrl, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        if (! resposta.ok) {
                            return;
                        }
                        const dados = await resposta.json();
                        this.status = dados.status;
                        this.progresso = dados.progresso ?? this.progresso;
                        this.etapa = dados.etapa || this.etapa;
                        this.erro = dados.erro || '';
                        if (dados.status === 'concluida' && dados.edit_url) {
                            clearInterval(this.timer);
                            this.progresso = 100;
                            this.etapa = @js(__('Concluída'));
                            window.location.href = dados.edit_url;
                        }
                        if (dados.status === 'falhou') {
                            clearInterval(this.timer);
                        }
                    } catch (e) {}
                },
            }"
            x-init="iniciar()"
        >
            <div class="rounded-lg bg-white p-6 shadow-sm sm:p-8">
                <p class="text-sm text-gray-600">
                    {{ __('Arquivo:') }}
                    <span class="font-medium text-gray-900">{{ $importacao->pdf_nome_original }}</span>
                </p>

                <div class="mt-6" x-show="!finalizada || falhou">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <svg
                                x-show="!falhou"
                                class="h-5 w-5 shrink-0 animate-spin text-indigo-600"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <p class="truncate text-sm font-medium text-gray-900" x-text="etapa"></p>
                        </div>
                        <p class="shrink-0 text-sm font-semibold tabular-nums text-indigo-600" x-show="!falhou">
                            <span x-text="percentual"></span>%
                        </p>
                    </div>

                    <div
                        class="mt-4 h-2.5 overflow-hidden rounded-full bg-gray-100"
                        role="progressbar"
                        :aria-valuenow="percentual"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-label="etapa"
                    >
                        <div
                            class="h-full rounded-full transition-all duration-500 ease-out"
                            :class="falhou ? 'bg-red-500' : 'bg-indigo-600'"
                            :style="`width: ${falhou ? Math.max(percentual, 8) : percentual}%`"
                        ></div>
                    </div>

                    <p class="mt-3 text-sm text-gray-500" x-show="!falhou">
                        {{ __('A IA está lendo o PDF e preenchendo as abas do artigo. Isso pode levar alguns minutos.') }}
                    </p>
                </div>

                <div class="mt-6 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700" x-show="falhou" x-cloak>
                    <p class="font-medium">{{ __('A importação falhou.') }}</p>
                    <p class="mt-1" x-text="erro"></p>
                </div>

                <div class="mt-8 flex items-center justify-between gap-3 border-t border-gray-100 pt-6">
                    <a href="{{ route('artigos.create') }}" class="text-sm text-gray-600 hover:text-gray-900">
                        {{ __('Voltar') }}
                    </a>
                    <a
                        href="{{ route('artigos.create') }}"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                        x-show="falhou"
                        x-cloak
                    >
                        {{ __('Tentar outro PDF') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
