<aside
    x-show="aberto"
    x-cloak
    x-transition:enter="transition ease-editorial duration-300"
    x-transition:enter-start="translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition ease-editorial duration-200"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="translate-x-full opacity-0"
    class="fixed inset-y-0 right-0 z-50 flex w-full max-w-md flex-col bg-ink text-paper shadow-2xl"
    role="dialog"
    aria-modal="true"
    :aria-label="@js(__('Referências'))"
    @keydown.escape.window="fechar()"
>
    <div class="flex items-center justify-between gap-3 border-b border-white/10 px-5 py-4">
        <h2 class="text-sm font-semibold uppercase tracking-[0.18em] text-accent-bright">
            {{ __('Referências') }}
        </h2>
        <button
            type="button"
            class="inline-flex size-9 items-center justify-center text-paper/70 transition hover:bg-white/10 hover:text-white"
            @click="fechar()"
            aria-label="{{ __('Fechar') }}"
        >
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-5 py-4" x-ref="listaPainel">
        <template x-for="referencia in referencias" :key="referencia.numero">
            <article
                class="border-b border-white/10 py-4"
                :id="'painel-ref-' + referencia.numero"
                :class="ativos.includes(referencia.numero) ? 'bg-white/10 px-3 -mx-3 rounded-sm' : ''"
            >
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-accent-bright" x-text="referencia.numero"></p>
                <p class="mt-2 text-sm leading-relaxed text-paper/90" x-text="referencia.texto"></p>
            </article>
        </template>
    </div>
</aside>

<div
    x-show="aberto"
    x-cloak
    class="fixed inset-0 z-40 bg-ink/40"
    @click="fechar()"
    aria-hidden="true"
></div>
