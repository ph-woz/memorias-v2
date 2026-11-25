{{--
  Painel lateral de referências (estilo portal Memórias).
  Estado em Alpine.store('artigoReferencias') — não depende do escopo
  x-data local, que o HTML importado do artigo pode quebrar.
--}}
<aside
    x-data
    x-show="$store.artigoReferencias.aberto"
    x-cloak
    x-transition:enter="transition ease-editorial duration-300"
    x-transition:enter-start="translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition ease-editorial duration-200"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="translate-x-full opacity-0"
    class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-[#0d4f4a] text-paper shadow-2xl"
    role="dialog"
    aria-modal="true"
    :aria-label="@js(__('Referências'))"
    @keydown.escape.window="$store.artigoReferencias.fechar()"
>
    <div class="flex items-center justify-between gap-3 border-b border-white/15 px-5 py-4">
        <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-[#9fd9c8]">
            {{ __('Referências') }}
        </h2>
        <button
            type="button"
            class="inline-flex size-9 items-center justify-center text-paper/70 transition hover:bg-white/10 hover:text-white"
            @click="$store.artigoReferencias.fechar()"
            aria-label="{{ __('Fechar') }}"
        >
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div id="painel-referencias-lista" class="flex-1 overflow-y-auto">
        <template x-for="referencia in $store.artigoReferencias.referencias" :key="referencia.numero">
            <article
                class="relative border-b border-white/10 px-5 py-4 transition"
                :id="'painel-ref-' + referencia.numero"
                :class="$store.artigoReferencias.estaAtivo(referencia.numero)
                    ? 'bg-black/50'
                    : ''"
            >
                <button
                    type="button"
                    class="absolute right-3 top-3 inline-flex size-7 items-center justify-center text-paper/50 transition hover:bg-white/10 hover:text-white"
                    x-show="$store.artigoReferencias.estaAtivo(referencia.numero)"
                    @click="$store.artigoReferencias.fechar()"
                    aria-label="{{ __('Fechar') }}"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
                <p class="pr-8 text-sm leading-relaxed text-paper/95" x-text="referencia.numero + '. ' + referencia.texto"></p>
            </article>
        </template>
    </div>
</aside>

<div
    x-data
    x-show="$store.artigoReferencias.aberto"
    x-cloak
    class="fixed inset-0 z-40 bg-ink/40"
    @click="$store.artigoReferencias.fechar()"
    aria-hidden="true"
></div>
