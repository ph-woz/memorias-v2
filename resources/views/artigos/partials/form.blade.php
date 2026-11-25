@php
    /** @var \App\Models\Artigo|null $artigo */
    $autoresPadrao = isset($artigo)
        ? $artigo->autores->pluck('nome')->all()
        : [auth()->user()->nome];
    $autoresSelecionados = old('autores', $autoresPadrao);
    $referenciasPadrao = isset($artigo)
        ? $artigo->referencias->pluck('texto')->all()
        : [];
    $referenciasSelecionadas = old('referencias', $referenciasPadrao);
    $categoriasPadrao = isset($artigo)
        ? $artigo->categorias->pluck('id')->all()
        : [];
    $categoriasSelecionadas = collect(old('categorias', $categoriasPadrao))
        ->map(static fn (mixed $id): int => (int) $id)
        ->filter(static fn (int $id): bool => $id > 0)
        ->values()
        ->all();
    $palavrasChavePadrao = isset($artigo)
        ? $artigo->palavrasChave->pluck('nome')->all()
        : [];
    $palavrasChaveSelecionadas = old('palavras_chave', $palavrasChavePadrao);
    $areasTematicasPadrao = isset($artigo)
        ? $artigo->areasTematicas->pluck('nome')->all()
        : [];
    $areasTematicasSelecionadas = old('areas_tematicas', $areasTematicasPadrao);
    $etiquetasPadrao = isset($artigo)
        ? $artigo->etiquetas->pluck('nome')->all()
        : [];
    $etiquetasSelecionadas = old('etiquetas', $etiquetasPadrao);

    $documentosPadrao = isset($artigo)
        ? $artigo->documentos->map(static fn ($documento): array => [
            'id' => $documento->id,
            'titulo' => $documento->titulo,
            'url' => $documento->url ?? '',
            'tipo' => filled($documento->url) ? 'url' : 'arquivo',
            'arquivoNome' => $documento->nome_arquivo ?? '',
            'arquivoUrl' => $documento->url_arquivo ?? '',
        ])->values()->all()
        : [];
    $documentosSelecionados = old('documentos', $documentosPadrao);

    $abaInicial = 'principal';
    if ($errors->hasAny(['conteudo'])) {
        $abaInicial = 'conteudo';
    } elseif ($errors->hasAny(['resumo'])) {
        $abaInicial = 'principal';
    } elseif ($errors->hasAny(['autores', 'autores.*'])) {
        $abaInicial = 'autores';
    } elseif ($errors->hasAny(['autor_correspondente'])) {
        $abaInicial = 'autor-correspondente';
    } elseif ($errors->hasAny(['endereco_instituicoes'])) {
        $abaInicial = 'endereco-instituicoes';
    } elseif ($errors->hasAny(['palavras_chave', 'palavras_chave.*'])) {
        $abaInicial = 'palavras-chave';
    } elseif ($errors->hasAny(['referencias', 'referencias.*'])) {
        $abaInicial = 'referencias';
    } elseif ($errors->hasAny(['documentos', 'documentos.*'])) {
        $abaInicial = 'documentos';
    } elseif ($errors->hasAny(['seo_titulo', 'seo_descricao', 'seo_palavras_chave'])) {
        $abaInicial = 'seo';
    }

    $estadoInicial = [
        'titulo' => old('titulo', $artigo->titulo ?? ''),
        'slug' => old('slug', $artigo->slug ?? ''),
        // No create, o slug acompanha o título. Na edição, preserva o slug até o usuário alterar.
        'slugManual' => isset($artigo) || old('slug') !== null,
        'fotoUrl' => $artigo->url_foto ?? '',
        'aba' => $abaInicial,
        'seoTitulo' => old('seo_titulo', $artigo->seo_titulo ?? ''),
        'seoDescricao' => old('seo_descricao', $artigo->seo_descricao ?? ''),
        'autores' => array_values(array_map('strval', (array) $autoresSelecionados)),
        'palavrasChave' => array_values(array_map('strval', (array) $palavrasChaveSelecionadas)),
        'opcoesPalavrasChave' => collect($palavrasChave ?? [])->pluck('nome')->values()->all(),
        'areasTematicas' => array_values(array_map('strval', (array) $areasTematicasSelecionadas)),
        'opcoesAreasTematicas' => collect($areasTematicas ?? [])->pluck('nome')->values()->all(),
        'etiquetas' => array_values(array_map('strval', (array) $etiquetasSelecionadas)),
        'opcoesEtiquetas' => collect($etiquetas ?? [])->map(static fn ($etiqueta): array => [
            'nome' => $etiqueta->nome,
            'cor' => $etiqueta->cor,
        ])->values()->all(),
        'referencias' => array_values(array_map('strval', (array) $referenciasSelecionadas)),
        'documentos' => array_values(array_map(static function (mixed $documento): array {
            $documento = is_array($documento) ? $documento : [];

            return [
                'id' => $documento['id'] ?? null,
                'titulo' => (string) ($documento['titulo'] ?? ''),
                'url' => (string) ($documento['url'] ?? ''),
                'tipo' => ($documento['tipo'] ?? null) === 'url' || filled($documento['url'] ?? null)
                    ? 'url'
                    : 'arquivo',
                'arquivoNome' => (string) ($documento['arquivoNome'] ?? ''),
                'arquivoUrl' => (string) ($documento['arquivoUrl'] ?? ''),
            ];
        }, (array) $documentosSelecionados)),
        'conteudoHtml' => old('conteudo', $artigo->conteudo ?? ''),
        'urlUploadConteudoImagem' => route('artigos.conteudo.imagens.store'),
    ];

    $abas = [
        'principal' => __('Principal'),
        'conteudo' => __('Conteúdo'),
        'autores' => __('Autores'),
        'autor-correspondente' => __('Autor correspondente'),
        'endereco-instituicoes' => __('Endereço das instituições'),
        'palavras-chave' => __('Palavras-chave'),
        'referencias' => __('Referências'),
        'documentos' => __('Documentos'),
        'seo' => __('SEO'),
    ];
@endphp

<div
    class="space-y-6"
    x-data="artigoFormulario(@js($estadoInicial))"
>
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex flex-wrap gap-x-6 gap-y-1" aria-label="{{ __('Abas do artigo') }}">
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
        <div>
            <x-input-label for="titulo" :value="__('Título')" />
            <x-text-input
                id="titulo"
                name="titulo"
                type="text"
                class="mt-1 block w-full"
                x-model="titulo"
                @input="sincronizarSlug()"
                required
                autofocus
            />
            <x-input-error class="mt-2" :messages="$errors->get('titulo')" />
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

        <div>
            <x-input-label for="resumo" :value="__('Resumo')" />
            @include('artigos.partials.editor-rico-campo', [
                'name' => 'resumo',
                'label' => __('Resumo'),
                'html' => old('resumo', $artigo->resumo ?? ''),
                'placeholder' => __('Escreva o resumo do artigo…'),
                'minHeight' => '9rem',
            ])
            <x-input-error class="mt-2" :messages="$errors->get('resumo')" />
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div class="min-w-0">
                <x-input-label :value="__('Áreas temáticas')" />
                <div class="relative mt-1" @click.outside="fecharSugestoesAreasTematicas()">
                    <div
                        class="flex min-h-[2.75rem] w-full cursor-text flex-wrap items-center gap-2 rounded-md border border-gray-300 bg-white px-2 py-2 shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500"
                        @click="$refs.areaTematicaInput.focus()"
                    >
                        <template x-for="(area, indice) in areasTematicas" :key="`${area}-${indice}`">
                            <span class="inline-flex items-center gap-1 rounded bg-indigo-600 px-2 py-1 text-xs font-medium text-white">
                                <span x-text="area"></span>
                                <input type="hidden" name="areas_tematicas[]" :value="area">
                                <button
                                    type="button"
                                    class="rounded p-0.5 leading-none hover:bg-indigo-500"
                                    @click.stop="removerAreaTematica(indice)"
                                    :aria-label="'{{ __('Remover') }} ' + area"
                                >
                                    ×
                                </button>
                            </span>
                        </template>

                        <input
                            type="text"
                            x-ref="areaTematicaInput"
                            class="min-w-[8rem] flex-1 border-0 bg-transparent p-1 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                            x-model="buscaAreaTematica"
                            @focus="abrirSugestoesAreasTematicas()"
                            @input="abrirSugestoesAreasTematicas()"
                            @keydown.enter.prevent="confirmarAreaTematica()"
                            @keydown.tab.prevent="confirmarAreaTematica()"
                            @keydown.escape.prevent="fecharSugestoesAreasTematicas()"
                            @keydown.arrow-down.prevent="navegarSugestaoAreaTematica(1)"
                            @keydown.arrow-up.prevent="navegarSugestaoAreaTematica(-1)"
                            @keydown.backspace="removerUltimaAreaTematicaSeVazio()"
                            placeholder="{{ __('Digite para buscar ou criar') }}"
                            autocomplete="off"
                        >
                    </div>

                    <ul
                        x-show="mostrarSugestoesAreasTematicas && sugestoesAreasTematicas.length > 0"
                        x-cloak
                        class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg"
                        role="listbox"
                    >
                        <template x-for="(sugestao, indice) in sugestoesAreasTematicas" :key="`${sugestao.rotulo}-${indice}`">
                            <li
                                class="cursor-pointer px-3 py-2 text-sm"
                                :class="indice === indiceSugestaoAreaTematica ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-indigo-50'"
                                @mousedown.prevent="selecionarSugestaoAreaTematica(sugestao)"
                                @mouseenter="indiceSugestaoAreaTematica = indice"
                                role="option"
                            >
                                <span x-text="sugestao.rotulo"></span>
                            </li>
                        </template>
                    </ul>
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('areas_tematicas')" />
                <x-input-error class="mt-2" :messages="$errors->get('areas_tematicas.*')" />
            </div>

            <div class="min-w-0">
                <x-input-label :value="__('Etiquetas')" />
                <div class="relative mt-1" @click.outside="fecharSugestoesEtiquetas()">
                    <div
                        class="flex min-h-[2.75rem] w-full cursor-text flex-wrap items-center gap-2 rounded-md border border-gray-300 bg-white px-2 py-2 shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500"
                        @click="$refs.etiquetaInput.focus()"
                    >
                        <template x-for="(etiqueta, indice) in etiquetas" :key="`${etiqueta}-${indice}`">
                            <span
                                class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium"
                                :style="estiloEtiqueta(etiqueta)"
                            >
                                <span x-text="etiqueta"></span>
                                <input type="hidden" name="etiquetas[]" :value="etiqueta">
                                <button
                                    type="button"
                                    class="rounded p-0.5 leading-none opacity-80 hover:opacity-100"
                                    @click.stop="removerEtiqueta(indice)"
                                    :aria-label="'{{ __('Remover') }} ' + etiqueta"
                                >
                                    ×
                                </button>
                            </span>
                        </template>

                        <input
                            type="text"
                            x-ref="etiquetaInput"
                            class="min-w-[8rem] flex-1 border-0 bg-transparent p-1 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                            x-model="buscaEtiqueta"
                            @focus="abrirSugestoesEtiquetas()"
                            @input="abrirSugestoesEtiquetas()"
                            @keydown.enter.prevent="confirmarEtiqueta()"
                            @keydown.tab.prevent="confirmarEtiqueta()"
                            @keydown.escape.prevent="fecharSugestoesEtiquetas()"
                            @keydown.arrow-down.prevent="navegarSugestaoEtiqueta(1)"
                            @keydown.arrow-up.prevent="navegarSugestaoEtiqueta(-1)"
                            @keydown.backspace="removerUltimaEtiquetaSeVazio()"
                            placeholder="{{ __('Digite para buscar ou criar') }}"
                            autocomplete="off"
                        >
                    </div>

                    <ul
                        x-show="mostrarSugestoesEtiquetas && sugestoesEtiquetas.length > 0"
                        x-cloak
                        class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg"
                        role="listbox"
                    >
                        <template x-for="(sugestao, indice) in sugestoesEtiquetas" :key="`${sugestao.rotulo}-${indice}`">
                            <li
                                class="cursor-pointer px-3 py-2 text-sm"
                                :class="indice === indiceSugestaoEtiqueta ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-indigo-50'"
                                @mousedown.prevent="selecionarSugestaoEtiqueta(sugestao)"
                                @mouseenter="indiceSugestaoEtiqueta = indice"
                                role="option"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <span
                                        x-show="!sugestao.criar"
                                        class="inline-block h-2.5 w-2.5 rounded-full"
                                        :style="`background-color: ${sugestao.cor || '#6366F1'}`"
                                    ></span>
                                    <span x-text="sugestao.rotulo"></span>
                                </span>
                            </li>
                        </template>
                    </ul>
                </div>
                <x-input-error class="mt-2" :messages="$errors->get('etiquetas')" />
                <x-input-error class="mt-2" :messages="$errors->get('etiquetas.*')" />
            </div>
        </div>

        <div>
            <x-input-label :value="__('Categorias')" />
            <div class="mt-2 flex flex-wrap gap-2 rounded-md border border-gray-200 bg-white px-3 py-3">
                @forelse ($categorias as $categoria)
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-gray-200 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50">
                        <input
                            type="checkbox"
                            name="categorias[]"
                            value="{{ $categoria->id }}"
                            class="h-3.5 w-3.5 shrink-0 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            @checked(in_array($categoria->id, $categoriasSelecionadas, true))
                        >
                        <span>{{ $categoria->nome }}</span>
                    </label>
                @empty
                    <p class="text-sm text-gray-500">{{ __('Nenhuma categoria cadastrada.') }}</p>
                @endforelse
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('categorias')" />
            <x-input-error class="mt-2" :messages="$errors->get('categorias.*')" />
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3 xl:items-end">
            <div class="min-w-0">
                <x-input-label for="doi" :value="__('DOI')" />
                <x-text-input
                    id="doi"
                    name="doi"
                    type="text"
                    class="mt-1 block h-10 w-full font-mono text-sm"
                    :value="old('doi', $artigo->doi ?? '')"
                    placeholder="{{ __('Ex.: 10.1000/xyz123') }}"
                />
                <x-input-error class="mt-2" :messages="$errors->get('doi')" />
            </div>

            <div class="min-w-0">
                <x-input-label for="status" :value="__('Status')" />
                <select
                    id="status"
                    name="status"
                    class="mt-1 block h-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    required
                >
                    @foreach ($statuses as $status)
                        <option
                            value="{{ $status->value }}"
                            @selected(old('status', $artigo->status->value ?? 'rascunho') === $status->value)
                        >
                            {{ $status->label() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('status')" />
            </div>

            <div class="min-w-0">
                <x-input-label for="publicado_em" :value="__('Data de publicação')" />
                <x-text-input
                    id="publicado_em"
                    name="publicado_em"
                    type="datetime-local"
                    class="mt-1 block h-10 w-full"
                    :value="old('publicado_em', isset($artigo) && $artigo->publicado_em ? $artigo->publicado_em->format('Y-m-d\\TH:i') : '')"
                />
                <x-input-error class="mt-2" :messages="$errors->get('publicado_em')" />
            </div>
        </div>
    </div>

    <div x-show="aba === 'conteudo'" x-cloak class="space-y-4">
        <div>
            <x-input-label for="conteudo" :value="__('Conteúdo')" />
            @include('artigos.partials.editor-conteudo')
            <x-input-error class="mt-2" :messages="$errors->get('conteudo')" />
        </div>
    </div>

    <div x-show="aba === 'autores'" x-cloak class="space-y-4">
        <div>
            <h3 class="text-sm font-semibold text-gray-900">{{ __('Autores') }}</h3>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
            <x-text-input
                type="text"
                class="block w-full"
                x-model="novoAutor"
                x-ref="novoAutorInput"
                @keydown.enter.prevent="adicionarAutor()"
                placeholder="{{ __('Nome do autor') }}"
            />
            <button
                type="button"
                class="inline-flex shrink-0 items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                @click="adicionarAutor()"
            >
                {{ __('Adicionar') }}
            </button>
        </div>

        <ul x-ref="listaAutores" class="space-y-2">
            <template x-for="(autor, indice) in autores" :key="autor.id">
                <li
                    class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2"
                    :data-indice="indice"
                    @dragover.prevent="arrastarSobreAutor($event)"
                    @drop.prevent
                >
                    <button
                        type="button"
                        class="cursor-grab select-none px-1 text-gray-400 active:cursor-grabbing"
                        draggable="true"
                        aria-label="{{ __('Arrastar para ordenar') }}"
                        @dragstart="iniciarArrasteAutor($event)"
                        @dragend="finalizarArrasteAutor()"
                    >
                        ⋮⋮
                    </button>
                    <input
                        type="text"
                        name="autores[]"
                        class="min-w-0 flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        x-model="autor.nome"
                        required
                    >
                    <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                        @click="removerAutor(indice)"
                    >
                        {{ __('Remover') }}
                    </button>
                </li>
            </template>
        </ul>

        <p x-show="autores.length === 0" class="text-sm text-gray-500">{{ __('Nenhum autor adicionado.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('autores')" />
        <x-input-error class="mt-2" :messages="$errors->get('autores.*')" />
    </div>

    <div x-show="aba === 'autor-correspondente'" x-cloak class="space-y-4">
        <div>
            <x-input-label for="autor_correspondente" :value="__('Autor correspondente')" />
            @include('artigos.partials.editor-rico-campo', [
                'name' => 'autor_correspondente',
                'label' => __('Autor correspondente'),
                'html' => old('autor_correspondente', $artigo->autor_correspondente ?? ''),
                'placeholder' => __('Dados do autor correspondente…'),
                'minHeight' => '12rem',
            ])
            <x-input-error class="mt-2" :messages="$errors->get('autor_correspondente')" />
        </div>
    </div>

    <div x-show="aba === 'endereco-instituicoes'" x-cloak class="space-y-4">
        <div>
            <x-input-label for="endereco_instituicoes" :value="__('Endereço das instituições')" />
            @include('artigos.partials.editor-rico-campo', [
                'name' => 'endereco_instituicoes',
                'label' => __('Endereço das instituições'),
                'html' => old('endereco_instituicoes', $artigo->endereco_instituicoes ?? ''),
                'placeholder' => __('Endereços institucionais dos autores…'),
                'minHeight' => '12rem',
            ])
            <x-input-error class="mt-2" :messages="$errors->get('endereco_instituicoes')" />
        </div>
    </div>

    <div x-show="aba === 'palavras-chave'" x-cloak class="space-y-4">
        <div>
            <h3 class="text-sm font-semibold text-gray-900">{{ __('Palavras-chave') }}</h3>
        </div>

        <div class="relative" @click.outside="fecharSugestoesPalavrasChave()">
            <div
                class="flex min-h-[2.75rem] w-full cursor-text flex-wrap items-center gap-2 rounded-md border border-gray-300 bg-white px-2 py-2 shadow-sm focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500"
                @click="$refs.palavraChaveInput.focus()"
            >
                <template x-for="(palavra, indice) in palavrasChave" :key="`${palavra}-${indice}`">
                    <span class="inline-flex items-center gap-1 rounded bg-indigo-600 px-2 py-1 text-xs font-medium text-white">
                        <span x-text="palavra"></span>
                        <input type="hidden" name="palavras_chave[]" :value="palavra">
                        <button
                            type="button"
                            class="rounded p-0.5 leading-none hover:bg-indigo-500"
                            @click.stop="removerPalavraChave(indice)"
                            :aria-label="'{{ __('Remover') }} ' + palavra"
                        >
                            ×
                        </button>
                    </span>
                </template>

                <input
                    type="text"
                    x-ref="palavraChaveInput"
                    class="min-w-[10rem] flex-1 border-0 bg-transparent p-1 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                    x-model="buscaPalavraChave"
                    @focus="abrirSugestoesPalavrasChave()"
                    @input="abrirSugestoesPalavrasChave()"
                    @keydown.enter.prevent="confirmarPalavraChave()"
                    @keydown.tab.prevent="confirmarPalavraChave()"
                    @keydown.escape.prevent="fecharSugestoesPalavrasChave()"
                    @keydown.arrow-down.prevent="navegarSugestaoPalavraChave(1)"
                    @keydown.arrow-up.prevent="navegarSugestaoPalavraChave(-1)"
                    @keydown.backspace="removerUltimaPalavraChaveSeVazio()"
                    placeholder="{{ __('Digite para buscar ou criar') }}"
                    autocomplete="off"
                >
            </div>

            <ul
                x-show="mostrarSugestoesPalavrasChave && sugestoesPalavrasChave.length > 0"
                x-cloak
                class="absolute z-20 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg"
                role="listbox"
            >
                <template x-for="(sugestao, indice) in sugestoesPalavrasChave" :key="`${sugestao.rotulo}-${indice}`">
                    <li
                        class="cursor-pointer px-3 py-2 text-sm"
                        :class="indice === indiceSugestaoPalavraChave ? 'bg-indigo-600 text-white' : 'text-gray-700 hover:bg-indigo-50'"
                        @mousedown.prevent="selecionarSugestaoPalavraChave(sugestao)"
                        @mouseenter="indiceSugestaoPalavraChave = indice"
                        role="option"
                    >
                        <span x-text="sugestao.rotulo"></span>
                    </li>
                </template>
            </ul>
        </div>

        <x-input-error class="mt-2" :messages="$errors->get('palavras_chave')" />
        <x-input-error class="mt-2" :messages="$errors->get('palavras_chave.*')" />
    </div>

    <div x-show="aba === 'referencias'" x-cloak class="space-y-4">
        <div>
            <h3 class="text-sm font-semibold text-gray-900">{{ __('Referências') }}</h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('A ordem define o número da citação (1, 2, 3…). No editor, use o botão [1] para inserir a citação no texto.') }}
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
            <x-text-input
                type="text"
                class="block w-full"
                x-model="novaReferencia"
                x-ref="novaReferenciaInput"
                @keydown.enter.prevent="adicionarReferencia()"
                placeholder="{{ __('Referência') }}"
            />
            <button
                type="button"
                class="inline-flex shrink-0 items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                @click="adicionarReferencia()"
            >
                {{ __('Adicionar') }}
            </button>
        </div>

        <ul x-ref="listaReferencias" class="space-y-2">
            <template x-for="(referencia, indice) in referencias" :key="referencia.id">
                <li
                    class="flex items-center gap-2 rounded-md border border-gray-200 bg-white px-3 py-2"
                    :data-indice="indice"
                    @dragover.prevent="arrastarSobreReferencia($event)"
                    @drop.prevent
                >
                    <button
                        type="button"
                        class="cursor-grab select-none px-1 text-gray-400 active:cursor-grabbing"
                        draggable="true"
                        aria-label="{{ __('Arrastar para ordenar') }}"
                        @dragstart="iniciarArrasteReferencia($event)"
                        @dragend="finalizarArrasteReferencia()"
                    >
                        ⋮⋮
                    </button>
                    <span
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-slate-100 text-xs font-semibold text-slate-700"
                        x-text="indice + 1"
                        :title="`{{ __('Número da citação') }} ${indice + 1}`"
                    ></span>
                    <input
                        type="text"
                        name="referencias[]"
                        class="min-w-0 flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        x-model="referencia.texto"
                        required
                    >
                    <button
                        type="button"
                        class="rounded px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                        @click="removerReferencia(indice)"
                    >
                        {{ __('Remover') }}
                    </button>
                </li>
            </template>
        </ul>

        <p x-show="referencias.length === 0" class="text-sm text-gray-500">{{ __('Nenhuma referência adicionada.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('referencias')" />
        <x-input-error class="mt-2" :messages="$errors->get('referencias.*')" />
    </div>

    <div x-show="aba === 'documentos'" x-cloak class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="text-sm font-semibold text-gray-900">{{ __('Documentos') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('Anexe arquivos ou vincule URLs. Arraste para reordenar.') }}</p>
            </div>
            <button
                type="button"
                class="inline-flex shrink-0 items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                @click="adicionarDocumento()"
            >
                {{ __('Adicionar documento') }}
            </button>
        </div>

        <ul class="space-y-3">
            <template x-for="(documento, indice) in documentos" :key="documento.clientId">
                <li
                    class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"
                    :data-indice="indice"
                    @dragover.prevent="arrastarSobreDocumento($event)"
                    @drop.prevent
                >
                    <div class="flex items-start gap-3">
                        <button
                            type="button"
                            class="mt-2 cursor-grab select-none px-1 text-gray-400 active:cursor-grabbing"
                            draggable="true"
                            aria-label="{{ __('Arrastar para ordenar') }}"
                            @dragstart="iniciarArrasteDocumento($event)"
                            @dragend="finalizarArrasteDocumento()"
                        >
                            ⋮⋮
                        </button>

                        <div class="min-w-0 flex-1 space-y-3">
                            <input type="hidden" :name="`documentos[${indice}][id]`" :value="documento.id ?? ''">

                            <div>
                                <label class="block text-sm font-medium text-gray-700" :for="`documento-titulo-${documento.clientId}`">
                                    {{ __('Título') }}
                                </label>
                                <input
                                    type="text"
                                    :id="`documento-titulo-${documento.clientId}`"
                                    :name="`documentos[${indice}][titulo]`"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    x-model="documento.titulo"
                                    placeholder="{{ __('Ex.: PDF completo, Anexo A…') }}"
                                >
                            </div>

                            <div class="grid grid-cols-1 items-start gap-3 sm:grid-cols-[auto_minmax(0,1fr)]">
                                <div>
                                    <p class="text-sm font-medium text-gray-700">{{ __('Origem') }}</p>
                                    <div class="mt-1 inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1">
                                        <button
                                            type="button"
                                            class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                                            :class="documento.tipo === 'arquivo'
                                                ? 'bg-white text-indigo-600 shadow-sm'
                                                : 'text-gray-500 hover:text-gray-700'"
                                            @click="definirTipoDocumento(indice, 'arquivo')"
                                        >
                                            {{ __('Arquivo') }}
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded-md px-3 py-1.5 text-xs font-semibold transition"
                                            :class="documento.tipo === 'url'
                                                ? 'bg-white text-indigo-600 shadow-sm'
                                                : 'text-gray-500 hover:text-gray-700'"
                                            @click="definirTipoDocumento(indice, 'url')"
                                        >
                                            {{ __('URL') }}
                                        </button>
                                    </div>
                                </div>

                                <template x-if="documento.tipo === 'arquivo'">
                                    <div class="min-w-0 space-y-2">
                                        <label class="block text-sm font-medium text-gray-700" :for="`documento-arquivo-${documento.clientId}`">
                                            {{ __('Arquivo') }}
                                        </label>
                                        <input
                                            type="file"
                                            :id="`documento-arquivo-${documento.clientId}`"
                                            :name="`documentos[${indice}][arquivo]`"
                                            accept=".pdf,.doc,.docx,.odt,.txt,.rtf,.zip,.jpg,.jpeg,.png,.webp,application/pdf"
                                            class="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                                            @change="atualizarNomeArquivoDocumento(indice, $event)"
                                        >
                                        <p
                                            x-show="documento.arquivoNovoNome || documento.arquivoNome"
                                            class="text-xs text-gray-500"
                                        >
                                            <span x-show="documento.arquivoNovoNome" x-text="`{{ __('Novo arquivo:') }} ${documento.arquivoNovoNome}`"></span>
                                            <span x-show="!documento.arquivoNovoNome && documento.arquivoNome">
                                                <span x-text="`{{ __('Atual:') }} ${documento.arquivoNome}`"></span>
                                                <a
                                                    x-show="documento.arquivoUrl"
                                                    :href="documento.arquivoUrl"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="ml-2 font-medium text-indigo-600 hover:text-indigo-500"
                                                >{{ __('Abrir') }}</a>
                                            </span>
                                        </p>
                                    </div>
                                </template>

                                <template x-if="documento.tipo === 'url'">
                                    <div class="min-w-0">
                                        <label class="block text-sm font-medium text-gray-700" :for="`documento-url-${documento.clientId}`">
                                            {{ __('URL') }}
                                        </label>
                                        <input
                                            type="url"
                                            :id="`documento-url-${documento.clientId}`"
                                            :name="`documentos[${indice}][url]`"
                                            class="mt-1 block w-full rounded-md border-gray-300 font-mono text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            x-model="documento.url"
                                            placeholder="{{ __('https://exemplo.com/arquivo.pdf') }}"
                                        >
                                    </div>
                                </template>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-md px-2 py-1 text-xs font-medium text-red-600 hover:bg-red-50"
                            @click="removerDocumento(indice)"
                        >
                            {{ __('Remover') }}
                        </button>
                    </div>
                </li>
            </template>
        </ul>

        <x-input-error class="mt-2" :messages="$errors->get('documentos')" />
        @foreach ($errors->getMessages() as $campo => $mensagens)
            @if (str_starts_with((string) $campo, 'documentos.'))
                <x-input-error class="mt-2" :messages="$mensagens" />
            @endif
        @endforeach
    </div>

    <div x-show="aba === 'seo'" x-cloak class="space-y-6">
        <div>
            <h3 class="text-sm font-semibold text-gray-900">{{ __('SEO') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('Metadados usados em buscadores e compartilhamento. Se vazios, o título do artigo será usado como fallback.') }}</p>
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
                value="{{ old('seo_palavras_chave', $artigo->seo_palavras_chave ?? '') }}"
                placeholder="{{ __('Exemplo: família, memórias, histórias...') }}"
            />
            <p class="mt-1 text-xs text-gray-500">{{ __('Separe os termos por vírgula.') }}</p>
            <x-input-error class="mt-2" :messages="$errors->get('seo_palavras_chave')" />
        </div>

        <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('Prévia no Google') }}</p>
            <p class="mt-2 truncate text-lg text-[#1a0dab]" x-text="seoTitulo || titulo || @js(__('Título do artigo'))"></p>
            <p class="truncate text-sm text-[#006621]" x-text="`{{ url('/artigo') }}/${slug || 'slug'}`"></p>
            <p class="mt-1 line-clamp-2 text-sm text-gray-600" x-text="seoDescricao || @js(__('A descrição SEO aparece aqui.'))"></p>
        </div>
    </div>
</div>
