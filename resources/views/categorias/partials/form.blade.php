@php
    /** @var \App\Models\Categoria|null $categoria */
    $estadoInicial = [
        'nome' => old('nome', $categoria->nome ?? ''),
        'identificador' => old('identificador', $categoria->identificador ?? ''),
        // No create, o slug acompanha o nome. Na edição, preserva o slug até o usuário alterar.
        'slugManual' => isset($categoria) || old('identificador') !== null,
        'fotoUrl' => $categoria->url_foto ?? '',
        'seoTitulo' => old('seo_titulo', $categoria->seo_titulo ?? ''),
        'seoDescricao' => old('seo_descricao', $categoria->seo_descricao ?? ''),
    ];
@endphp

<div
    class="space-y-8"
    x-data="categoriaFormulario(@js($estadoInicial))"
>
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_16rem]">
        <div class="space-y-6">
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
                <x-input-label for="identificador" :value="__('Slug')" />
                <x-text-input
                    id="identificador"
                    name="identificador"
                    type="text"
                    class="mt-1 block w-full font-mono text-sm"
                    x-model="identificador"
                    @input="slugManual = true"
                    required
                />
                <p class="mt-1 text-xs text-gray-500">{{ __('Usado na URL da categoria. Gerado automaticamente a partir do nome; você pode ajustar.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('identificador')" />
            </div>

            <div>
                <x-input-label for="foto" :value="isset($categoria) ? __('Trocar foto') : __('Foto')" />
                <input
                    id="foto"
                    name="foto"
                    type="file"
                    accept="image/*"
                    class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                    @change="atualizarFoto($event)"
                    @unless(isset($categoria)) required @endunless
                />
                <p class="mt-1 text-xs text-gray-500">{{ __('JPG, PNG ou WEBP até 5MB.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('foto')" />
            </div>
        </div>

        <div class="space-y-2">
            <p class="text-sm font-medium text-gray-700">{{ __('Pré-visualização') }}</p>
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50">
                <template x-if="fotoUrl">
                    <img :src="fotoUrl" alt="" class="aspect-[4/3] w-full object-cover">
                </template>
                <div
                    x-show="!fotoUrl"
                    class="flex aspect-[4/3] items-center justify-center px-4 text-center text-sm text-gray-400"
                >
                    {{ __('Nenhuma foto selecionada') }}
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6 border-t border-gray-100 pt-8">
        <div>
            <h3 class="text-base font-semibold text-gray-900">{{ __('SEO') }}</h3>
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
                placeholder="{{ __('família, memórias, histórias (separadas por vírgula)') }}"
            />
            <p class="mt-1 text-xs text-gray-500">{{ __('Opcional. Separe os termos por vírgula.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('seo_palavras_chave')" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Prévia no Google') }}</p>
            <p class="mt-2 truncate text-lg text-[#1a0dab]" x-text="seoTitulo || nome || @js(__('Título da categoria'))"></p>
            <p class="truncate text-sm text-[#006621]" x-text="`{{ url('/categoria') }}/${identificador || 'slug'}`"></p>
            <p class="mt-1 line-clamp-2 text-sm text-gray-600" x-text="seoDescricao || @js(__('A descrição SEO aparece aqui.'))"></p>
        </div>
    </div>
</div>
