@php
    /** @var \App\Models\MenuItem|null $menuItem */
    $linksIniciais = old('links');

    if (! is_array($linksIniciais)) {
        $linksIniciais = ($menuItem?->filhos ?? collect())
            ->map(fn ($filho) => [
                'id' => $filho->id,
                'rotulo' => $filho->rotulo,
                'url' => $filho->url,
                'target_blank' => (bool) $filho->target_blank,
            ])
            ->values()
            ->all();
    }

    $estadoInicial = [
        'links' => $linksIniciais,
    ];
@endphp

<div
    class="space-y-6"
    x-data="menuItemFormulario(@js($estadoInicial))"
>
    <div>
        <x-input-label for="rotulo" :value="__('Texto')" />
        <x-text-input
            id="rotulo"
            name="rotulo"
            type="text"
            class="mt-1 block w-full"
            :value="old('rotulo', $menuItem->rotulo ?? '')"
            required
            autofocus
            placeholder="{{ __('Ex.: Artigos') }}"
        />
        <x-input-error class="mt-2" :messages="$errors->get('rotulo')" />
    </div>

    <div>
        <x-input-label for="url" :value="__('URL')" />
        <x-text-input
            id="url"
            name="url"
            type="text"
            class="mt-1 block w-full font-mono text-sm"
            :value="old('url', $menuItem->url ?? '')"
            placeholder="{{ __('https://... ou /artigos') }}"
            x-bind:required="links.length === 0"
        />
        <x-input-error class="mt-2" :messages="$errors->get('url')" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <label class="flex items-center gap-3 rounded-md border border-gray-200 px-4 py-3">
            <input
                type="checkbox"
                name="target_blank"
                value="1"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                @checked((bool) old('target_blank', $menuItem->target_blank ?? false))
            >
            <span>
                <span class="block text-sm font-medium text-gray-900">{{ __('Abrir em nova aba') }}</span>
                <span class="block text-xs text-gray-500">{{ __('Vale para o item principal') }}</span>
            </span>
        </label>

        <label class="flex items-center gap-3 rounded-md border border-gray-200 px-4 py-3">
            <input
                type="checkbox"
                name="ativo"
                value="1"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                @checked((bool) old('ativo', $menuItem->ativo ?? true))
            >
            <span>
                <span class="block text-sm font-medium text-gray-900">{{ __('Ativo') }}</span>
                <span class="block text-xs text-gray-500">{{ __('Visível no menu do site') }}</span>
            </span>
        </label>
    </div>

    <div class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <x-input-label :value="__('Links')" />
            <x-input-error :messages="$errors->get('links')" />
        </div>

        <template x-if="links.length === 0">
            <div class="rounded-md border border-dashed border-gray-300 px-4 py-8 text-center text-sm text-gray-500">
                {{ __('Nenhum link adicionado.') }}
            </div>
        </template>

        <div class="space-y-3" x-show="links.length > 0">
            <template x-for="(link, indice) in links" :key="link.key">
                <div class="rounded-md border border-gray-200 bg-gray-50/70 p-3">
                    <input type="hidden" :name="`links[${indice}][id]`" :value="link.id ?? ''">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                        <div class="min-w-0 flex-1 space-y-3 sm:grid sm:grid-cols-2 sm:gap-3 sm:space-y-0">
                            <div>
                                <x-input-label :value="__('Texto do link')" />
                                <input
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    :name="`links[${indice}][rotulo]`"
                                    x-model="link.rotulo"
                                    placeholder="{{ __('Título do link') }}"
                                    required
                                >
                            </div>
                            <div>
                                <x-input-label :value="__('URL')" />
                                <input
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    :name="`links[${indice}][url]`"
                                    x-model="link.url"
                                    placeholder="{{ __('https://... ou /caminho') }}"
                                    required
                                >
                            </div>
                        </div>

                        <div class="flex items-center gap-2 sm:pt-7">
                            <label class="inline-flex items-center gap-2 text-xs text-gray-600" :title="__('Abrir em nova aba')">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    :name="`links[${indice}][target_blank]`"
                                    value="1"
                                    x-model="link.target_blank"
                                >
                                <span>{{ __('Nova aba') }}</span>
                            </label>

                            <button
                                type="button"
                                class="inline-flex rounded-md p-1.5 text-red-600 hover:bg-red-50"
                                @click="removerLink(indice)"
                                :aria-label="__('Remover link')"
                                :title="__('Remover link')"
                            >
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-md px-2 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50"
            @click="adicionarLink()"
        >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            {{ __('Adicionar link') }}
        </button>
    </div>
</div>
