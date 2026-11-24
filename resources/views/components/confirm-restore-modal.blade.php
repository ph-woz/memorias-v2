<div
    x-data="{
        open: false,
        action: '',
        title: '',
        message: '',
        confirmLabel: '',
        openModal(detail) {
            this.action = detail.action ?? '';
            this.title = detail.title ?? '';
            this.message = detail.message ?? '';
            this.confirmLabel = detail.confirmLabel ?? @js(__('Restaurar'));
            this.open = true;
            document.body.classList.add('overflow-y-hidden');
            this.$nextTick(() => this.$refs.confirmButton?.focus());
        },
        close() {
            this.open = false;
            document.body.classList.remove('overflow-y-hidden');
        },
    }"
    x-on:confirm-restore.window="openModal($event.detail)"
    x-on:keydown.escape.window="open && close()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-live="assertive"
>
    <div class="flex min-h-full items-end justify-center p-4 sm:items-center sm:p-0">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900/50 backdrop-blur-[1px]"
            x-on:click="close()"
            aria-hidden="true"
        ></div>

        <div
            x-show="open"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            role="dialog"
            aria-modal="true"
            aria-labelledby="confirm-restore-title"
            class="relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-black/5"
        >
            <form method="POST" :action="action" class="p-6">
                @csrf

                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <h2 id="confirm-restore-title" class="text-base font-semibold text-gray-900" x-text="title"></h2>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600" x-text="message"></p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <x-secondary-button type="button" class="justify-center" x-on:click="close()">
                        {{ __('Cancelar') }}
                    </x-secondary-button>

                    <x-primary-button
                        type="submit"
                        class="justify-center"
                        x-ref="confirmButton"
                    >
                        <span x-text="confirmLabel">{{ __('Restaurar') }}</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
