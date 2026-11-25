@php
    /** @var \App\Models\Categoria|null $categoria */
    $abaInicial = 'principal';
    if ($errors->hasAny(['seo_titulo', 'seo_descricao', 'seo_palavras_chave'])) {
        $abaInicial = 'seo';
    }

    $estadoInicial = [
        'nome' => old('nome', $categoria->nome ?? ''),
        'slug' => old('slug', $categoria->slug ?? ''),
        // No create, o slug acompanha o nome. Na edição, preserva o slug até o usuário alterar.
        'slugManual' => isset($categoria) || old('slug') !== null,
        'fotoUrl' => $categoria->url_foto ?? '',
        'aba' => $abaInicial,
        'seoTitulo' => old('seo_titulo', $categoria->seo_titulo ?? ''),
        'seoDescricao' => old('seo_descricao', $categoria->seo_descricao ?? ''),
    ];

    $abas = [
        'principal' => __('Principal'),
        'seo' => __('SEO'),
    ];
@endphp

<div
    class="space-y-6"
    x-data="categoriaFormulario(@js($estadoInicial))"
>
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex flex-wrap gap-x-6 gap-y-1" aria-label="{{ __('Abas da categoria') }}">
            @foreach ($abas as $chave => $rotulo)
                <button
                    type="button"
                    class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition"
                    :class="aba === @js($chave)
                        ? 'border-indigo-600 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    @click="aba = @js($chave)"
                >
                    {{ $rotulo }}
                </button>
            @endforeach
        </nav>
    </div>

    <div x-show="aba === 'principal'" x-cloak class="space-y-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="nome" :value="__('Nome')" />
                <x-text-input
                    id="nome"
                    name="nome"
                    type="text"
                    class="mt-1 block w-full"
                    x-model="nome"
                    @input="sincronizarSlug()"
                    required
                    autofocus
                />
                <x-input-error class="mt-2" :messages="$errors->get('nome')" />
            </div>

            <div>
                <x-input-label for="slug" :value="__('Slug')" />
                <x-text-input
                    id="slug"
                    name="slug"
                    type="text"
                    class="mt-1 block w-full font-mono text-sm"
                    x-model="slug"
                    @input="slugManual = true"
                    required
                />
                <x-input-error class="mt-2" :messages="$errors->get('slug')" />
            </div>
        </div>

        <div>
            <x-input-label for="foto" :value="__('Foto')" />
            <input
                id="foto"
                x-ref="fotoInput"
                name="foto"
                type="file"
                accept="image/jpeg,image/png,image/webp,image/gif"
                class="sr-only"
                @change="abrirCrop($event)"
            />
            <button
                type="button"
                class="group relative mt-1 flex h-36 w-36 items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-gray-300 bg-gray-50 text-gray-500 transition hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                @click="prepararSelecao(); $refs.fotoInput.click()"
                :aria-label="fotoUrl ? '{{ __('Trocar foto') }}' : '{{ __('Adicionar foto') }}'"
            >
                <template x-if="fotoUrl">
                    <img :src="fotoUrl" alt="" class="absolute inset-0 h-full w-full object-cover">
                </template>
                <span
                    class="relative z-10 flex flex-col items-center gap-1 px-3 text-center text-xs font-medium"
                    :class="fotoUrl ? 'rounded-md bg-black/50 px-2 py-1 text-white opacity-0 transition group-hover:opacity-100' : ''"
                    x-text="fotoUrl ? '{{ __('Trocar') }}' : '{{ __('Clique para adicionar') }}'"
                ></span>
            </button>
            <x-input-error class="mt-2" :messages="$errors->get('foto')" />
        </div>

        <div
            x-show="cropAberto"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4"
            @keydown.escape.window="cancelarCrop()"
        >
            <div
                class="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl"
                @click.outside="cancelarCrop()"
            >
                <h3 class="text-base font-semibold text-gray-900">{{ __('Ajustar foto') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('Arraste para reposicionar e use o zoom para enquadrar.') }}</p>

                <div
                    class="relative mx-auto mt-4 h-72 w-72 touch-none overflow-hidden rounded-xl bg-gray-900"
                    @pointerdown="iniciarArraste($event)"
                    @pointermove="arrastar($event)"
                    @pointerup="pararArraste()"
                    @pointercancel="pararArraste()"
                    @pointerleave="pararArraste()"
                >
                    <img
                        x-ref="cropImagem"
                        :src="cropSrc"
                        alt=""
                        class="absolute left-0 top-0 max-w-none select-none"
                        draggable="false"
                        :style="estiloCropImg()"
                        @load="ajustarCrop()"
                    >
                </div>

                <label class="mt-4 block text-sm font-medium text-gray-700">
                    {{ __('Zoom') }}
                    <input
                        type="range"
                        min="1"
                        max="3"
                        step="0.01"
                        class="mt-2 w-full"
                        x-model.number="zoom"
                        @input="limitarOffset()"
                    >
                </label>

                <div class="mt-5 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-md px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100"
                        @click="cancelarCrop()"
                        :disabled="processandoCrop"
                    >
                        {{ __('Cancelar') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
                        @click="aplicarCrop()"
                        :disabled="processandoCrop"
                    >
                        <span x-text="processandoCrop ? '{{ __('Processando…') }}' : '{{ __('Usar foto') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="aba === 'seo'" x-cloak class="space-y-6">
        <div>
            <h3 class="text-sm font-semibold text-gray-900">{{ __('SEO') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('Metadados usados em buscadores e compartilhamento. Se vazios, o nome da categoria será usado como fallback.') }}</p>
        </div>

        <div>
            <div class="flex items-center justify-between gap-3">
                <x-input-label for="seo_titulo" :value="__('Título SEO')" />
                <span class="text-xs text-gray-400" x-text="`${seoTitulo.length}/70`"></span>
            </div>
            <x-text-input
                id="seo_titulo"
                name="seo_titulo"
                type="text"
                class="mt-1 block w-full"
                maxlength="70"
                x-model="seoTitulo"
                placeholder="{{ __('Ex.: Memórias de família para guardar e compartilhar') }}"
            />
            <x-input-error class="mt-2" :messages="$errors->get('seo_titulo')" />
        </div>

        <div>
            <div class="flex items-center justify-between gap-3">
                <x-input-label for="seo_descricao" :value="__('Descrição SEO')" />
                <span class="text-xs text-gray-400" x-text="`${seoDescricao.length}/160`"></span>
            </div>
            <textarea
                id="seo_descricao"
                name="seo_descricao"
                rows="3"
                maxlength="160"
                x-model="seoDescricao"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="{{ __('Resumo curto para aparecer nos resultados de busca.') }}"
            ></textarea>
            <x-input-error class="mt-2" :messages="$errors->get('seo_descricao')" />
        </div>

        <div>
            <x-input-label for="seo_palavras_chave" :value="__('Palavras-chave SEO')" />
            <x-text-input
                id="seo_palavras_chave"
                name="seo_palavras_chave"
                type="text"
                class="mt-1 block w-full"
                value="{{ old('seo_palavras_chave', $categoria->seo_palavras_chave ?? '') }}"
                placeholder="{{ __('Exemplo: família, memórias, histórias...') }}"
            />
            <p class="mt-1 text-xs text-gray-500">{{ __('Separe os termos por vírgula.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('seo_palavras_chave')" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Prévia no Google') }}</p>
            <p class="mt-2 truncate text-lg text-[#1a0dab]" x-text="seoTitulo || nome || @js(__('Título da categoria'))"></p>
            <p class="truncate text-sm text-[#006621]" x-text="`{{ url('/categoria') }}/${slug || 'slug'}`"></p>
            <p class="mt-1 line-clamp-2 text-sm text-gray-600" x-text="seoDescricao || @js(__('A descrição SEO aparece aqui.'))"></p>
        </div>
    </div>
</div>
