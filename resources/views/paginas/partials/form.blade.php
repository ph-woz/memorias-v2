@php
    /** @var \App\Models\Pagina|null $pagina */
    $abaInicial = 'principal';
    if ($errors->hasAny(['seo_titulo', 'seo_descricao', 'seo_palavras_chave'])) {
        $abaInicial = 'seo';
    }

    $estadoInicial = [
        'titulo' => old('titulo', $pagina->titulo ?? ''),
        'slug' => old('slug', $pagina->slug ?? ''),
        'slugManual' => isset($pagina) || old('slug') !== null,
        'conteudoHtml' => old('conteudo', $pagina->conteudo ?? ''),
        'urlUploadConteudoImagem' => route('paginas.conteudo.imagens.store'),
        'ativo' => (bool) old('ativo', $pagina->ativo ?? true),
        'aba' => $abaInicial,
        'seoTitulo' => old('seo_titulo', $pagina->seo_titulo ?? ''),
        'seoDescricao' => old('seo_descricao', $pagina->seo_descricao ?? ''),
    ];

    $abas = [
        'principal' => __('Principal'),
        'seo' => __('SEO'),
    ];
@endphp

<script type="application/json" id="pagina-formulario-estado">@json($estadoInicial)</script>
<div
    class="space-y-6"
    x-data="paginaFormulario('pagina-formulario-estado')"
>
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex flex-wrap gap-x-6 gap-y-1" aria-label="{{ __('Abas da página') }}">
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
                <x-input-label for="titulo" :value="__('Título')" />
                <x-text-input
                    id="titulo"
                    name="titulo"
                    type="text"
                    class="mt-1 block w-full"
                    x-model="titulo"
                    @input="sincronizarSlugDoTitulo()"
                    required
                    autofocus
                />
                <x-input-error class="mt-2" :messages="$errors->get('titulo')" />
            </div>

            <div>
                <x-input-label for="slug" :value="__('URL')" />
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
            <x-input-label for="conteudo" :value="__('Conteúdo')" />
            @include('artigos.partials.editor-conteudo')
            <x-input-error class="mt-2" :messages="$errors->get('conteudo')" />
        </div>

        <label class="flex items-center gap-3 rounded-md border border-gray-200 px-4 py-3">
            <input
                type="checkbox"
                name="ativo"
                value="1"
                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                x-model="ativo"
            >
            <span>
                <span class="block text-sm font-medium text-gray-900">{{ __('Ativa') }}</span>
                <span class="block text-xs text-gray-500">{{ __('Visível em') }} <span class="font-mono" x-text="'/' + (slug || '...')"></span></span>
            </span>
        </label>
    </div>

    <div x-show="aba === 'seo'" x-cloak class="space-y-6">
        <div>
            <h3 class="text-sm font-semibold text-gray-900">{{ __('SEO') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('Metadados usados em buscadores e compartilhamento. Se vazios, o título da página será usado como fallback.') }}</p>
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
                placeholder="{{ __('Ex.: Política editorial — Memórias do IOC') }}"
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
                value="{{ old('seo_palavras_chave', $pagina->seo_palavras_chave ?? '') }}"
                placeholder="{{ __('Exemplo: política, editorial, submissão...') }}"
            />
            <p class="mt-1 text-xs text-gray-500">{{ __('Separe os termos por vírgula.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('seo_palavras_chave')" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Prévia no Google') }}</p>
            <p class="mt-2 truncate text-lg text-[#1a0dab]" x-text="seoTitulo || titulo || @js(__('Título da página'))"></p>
            <p class="truncate text-sm text-[#006621]" x-text="`{{ url('/') }}/${slug || 'slug'}`"></p>
            <p class="mt-1 line-clamp-2 text-sm text-gray-600" x-text="seoDescricao || @js(__('A descrição SEO aparece aqui.'))"></p>
        </div>
    </div>
</div>
