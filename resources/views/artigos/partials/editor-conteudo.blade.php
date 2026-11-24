@php
    /** Toolbar TipTap no estilo TinyMCE / Google Docs. */
@endphp

<input type="hidden" name="conteudo" :value="conteudoHtml">
<input
    type="file"
    x-ref="imagemConteudoInput"
    class="hidden"
    accept="image/jpeg,image/png,image/webp,image/gif"
    @change="enviarImagemConteudo($event)"
>

<div
    class="editor-shell mt-1 overflow-hidden rounded-md border border-gray-300 bg-white shadow-sm"
    :class="editorTelaCheia ? 'editor-shell--fullscreen' : ''"
>
    <div
        class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-gray-50 px-2 py-2"
        @mousedown="if (! $event.target.closest('input, select, textarea, button[data-allow-focus]')) { $event.preventDefault() }"
    >
        <button type="button" :class="classeBotaoEditor('undo')" @click="comandoEditor('undo')" title="{{ __('Desfazer') }}">↩</button>
        <button type="button" :class="classeBotaoEditor('redo')" @click="comandoEditor('redo')" title="{{ __('Refazer') }}">↪</button>

        <span class="mx-1 h-5 w-px bg-gray-300" aria-hidden="true"></span>

        <select
            class="h-9 rounded-md border-gray-300 py-1.5 text-sm leading-5 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :value="blocoAtual()"
            @change="definirBloco($event.target.value)"
            title="{{ __('Estilo do bloco') }}"
        >
            <option value="paragraph">{{ __('Parágrafo') }}</option>
            <option value="heading:1">{{ __('Título 1') }}</option>
            <option value="heading:2">{{ __('Título 2') }}</option>
            <option value="heading:3">{{ __('Título 3') }}</option>
            <option value="heading:4">{{ __('Título 4') }}</option>
            <option value="blockquote">{{ __('Citação') }}</option>
            <option value="codeBlock">{{ __('Bloco de código') }}</option>
        </select>

        <select
            class="h-9 max-w-[10rem] rounded-md border-gray-300 py-1.5 text-sm leading-5 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :value="fonteAtual()"
            @change="comandoEditor('fontFamily', { fontFamily: $event.target.value })"
            title="{{ __('Fonte') }}"
        >
            <template x-for="fonte in fontesTexto" :key="fonte.valor || 'padrao'">
                <option :value="fonte.valor" x-text="fonte.rotulo"></option>
            </template>
        </select>

        <select
            class="h-9 rounded-md border-gray-300 py-1.5 text-sm leading-5 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :value="tamanhoFonteAtual()"
            @change="comandoEditor('fontSize', { fontSize: $event.target.value })"
            title="{{ __('Tamanho da fonte') }}"
        >
            <template x-for="tamanho in tamanhosFonte" :key="tamanho">
                <option :value="tamanho" x-text="tamanho"></option>
            </template>
        </select>

        <span class="mx-1 h-5 w-px bg-gray-300" aria-hidden="true"></span>

        <button type="button" :class="classeBotaoEditor('bold')" @click="comandoEditor('bold')" title="{{ __('Negrito') }}"><span class="font-bold">B</span></button>
        <button type="button" :class="classeBotaoEditor('italic')" @click="comandoEditor('italic')" title="{{ __('Itálico') }}"><span class="italic">I</span></button>
        <button type="button" :class="classeBotaoEditor('underline')" @click="comandoEditor('underline')" title="{{ __('Sublinhado') }}"><span class="underline">U</span></button>
        <button type="button" :class="classeBotaoEditor('strike')" @click="comandoEditor('strike')" title="{{ __('Riscado') }}"><span class="line-through">S</span></button>
        <button type="button" :class="classeBotaoEditor('subscript')" @click="comandoEditor('subscript')" title="{{ __('Subscrito') }}">X₂</button>
        <button type="button" :class="classeBotaoEditor('superscript')" @click="comandoEditor('superscript')" title="{{ __('Sobrescrito') }}">X²</button>
        <button
            type="button"
            :class="classeBotaoEditor('citacao')"
            @click="comandoEditor('citacao')"
            title="{{ __('Inserir citação de referência') }}"
        >
            [1]
        </button>

        <div class="relative">
            <button
                type="button"
                class="flex flex-col items-center rounded-md px-2 py-1 text-sm font-semibold text-gray-700 hover:bg-gray-100"
                @click="painelCorTexto = ! painelCorTexto; painelDestaque = false"
                title="{{ __('Cor do texto') }}"
            >
                <span>A</span>
                <span class="mt-0.5 h-1 w-4 rounded-sm" :style="`background-color: ${corTextoAtual()}`"></span>
            </button>
            <div
                x-show="painelCorTexto"
                x-cloak
                @click.outside="painelCorTexto = false"
                class="absolute left-0 z-30 mt-1 w-52 rounded-md border border-gray-200 bg-white p-2 shadow-lg"
            >
                <div class="mb-2 flex flex-wrap gap-1">
                    <template x-for="cor in coresTexto" :key="'texto-' + cor">
                        <button
                            type="button"
                            class="h-6 w-6 rounded-full border border-gray-300"
                            :class="editorEstaAtivo('color', { color: cor }) ? 'ring-2 ring-indigo-500 ring-offset-1' : ''"
                            :style="`background-color: ${cor}`"
                            @click="comandoEditor('color', { color: cor }); painelCorTexto = false"
                        ></button>
                    </template>
                    <label class="relative flex h-6 w-6 cursor-pointer overflow-hidden rounded-full border border-dashed border-gray-400" title="{{ __('Cor personalizada') }}">
                        <span class="pointer-events-none absolute inset-0 bg-[conic-gradient(red,yellow,lime,aqua,blue,magenta,red)] opacity-80"></span>
                        <input
                            type="color"
                            class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                            :value="corTextoAtual()"
                            @input="comandoEditor('color', { color: $event.target.value })"
                        >
                    </label>
                </div>
                <button type="button" class="w-full rounded px-2 py-1 text-left text-xs text-gray-600 hover:bg-gray-100" @click="comandoEditor('unsetColor'); painelCorTexto = false">
                    {{ __('Sem cor') }}
                </button>
            </div>
        </div>

        <div class="relative">
            <button
                type="button"
                class="flex flex-col items-center rounded-md px-2 py-1 text-sm font-semibold text-gray-700 hover:bg-gray-100"
                @click="painelDestaque = ! painelDestaque; painelCorTexto = false"
                title="{{ __('Cor de destaque') }}"
            >
                <span class="rounded bg-yellow-200 px-1">A</span>
            </button>
            <div
                x-show="painelDestaque"
                x-cloak
                @click.outside="painelDestaque = false"
                class="absolute left-0 z-30 mt-1 w-48 rounded-md border border-gray-200 bg-white p-2 shadow-lg"
            >
                <div class="mb-2 flex flex-wrap gap-1">
                    <template x-for="cor in coresDestaque" :key="'destaque-' + cor">
                        <button
                            type="button"
                            class="h-6 w-6 rounded border border-gray-300"
                            :class="editorEstaAtivo('highlight', { color: cor }) ? 'ring-2 ring-indigo-500 ring-offset-1' : ''"
                            :style="`background-color: ${cor}`"
                            @click="comandoEditor('highlight', { color: cor }); painelDestaque = false"
                        ></button>
                    </template>
                </div>
                <button type="button" class="w-full rounded px-2 py-1 text-left text-xs text-gray-600 hover:bg-gray-100" @click="comandoEditor('unsetHighlight'); painelDestaque = false">
                    {{ __('Remover destaque') }}
                </button>
            </div>
        </div>

        <span class="mx-1 h-5 w-px bg-gray-300" aria-hidden="true"></span>

        <button type="button" :class="classeBotaoEditor('link')" @click="adicionarLinkConteudo()" title="{{ __('Link') }}">{{ __('Link') }}</button>
        <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-50"
            :class="editorEstaAtivo('image') ? 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200' : ''"
            :disabled="enviandoImagemConteudo"
            @click="abrirModalImagem()"
            title="{{ __('Inserir imagem') }}"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                <circle cx="8.5" cy="10" r="1.5"></circle>
                <path d="M21 16l-5.5-5.5L7 19"></path>
            </svg>
            <span x-text="enviandoImagemConteudo ? '{{ __('Enviando…') }}' : '{{ __('Imagem') }}'"></span>
        </button>
        <button type="button" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100" @click="comandoEditor('insertTable')" title="{{ __('Inserir tabela') }}">{{ __('Tabela') }}</button>
        <button type="button" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100" @click="comandoEditor('horizontalRule')" title="{{ __('Linha horizontal') }}">{{ __('Linha') }}</button>

        <span class="mx-1 h-5 w-px bg-gray-300" aria-hidden="true"></span>

        <button type="button" :class="classeBotaoEditor('textAlign', { align: 'left' })" @click="comandoEditor('align', { align: 'left' })" title="{{ __('Esquerda') }}">{{ __('Esq') }}</button>
        <button type="button" :class="classeBotaoEditor('textAlign', { align: 'center' })" @click="comandoEditor('align', { align: 'center' })" title="{{ __('Centro') }}">{{ __('Cen') }}</button>
        <button type="button" :class="classeBotaoEditor('textAlign', { align: 'right' })" @click="comandoEditor('align', { align: 'right' })" title="{{ __('Direita') }}">{{ __('Dir') }}</button>
        <button type="button" :class="classeBotaoEditor('textAlign', { align: 'justify' })" @click="comandoEditor('align', { align: 'justify' })" title="{{ __('Justificar') }}">{{ __('Just') }}</button>

        <span class="mx-1 h-5 w-px bg-gray-300" aria-hidden="true"></span>

        <button type="button" :class="classeBotaoEditor('bulletList')" @click="comandoEditor('bulletList')" title="{{ __('Lista') }}">{{ __('Lista') }}</button>
        <button type="button" :class="classeBotaoEditor('orderedList')" @click="comandoEditor('orderedList')" title="{{ __('Lista numerada') }}">1.2.3</button>
        <button type="button" :class="classeBotaoEditor('taskList')" @click="comandoEditor('taskList')" title="{{ __('Lista de tarefas') }}">☑</button>
        <button type="button" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100" @click="comandoEditor('outdent')" title="{{ __('Diminuir recuo') }}">⇤</button>
        <button type="button" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100" @click="comandoEditor('indent')" title="{{ __('Aumentar recuo') }}">⇥</button>

        <span class="mx-1 h-5 w-px bg-gray-300" aria-hidden="true"></span>

        <button type="button" class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100" @click="comandoEditor('clear')" title="{{ __('Limpar formatação') }}">Tx</button>
        <button type="button" :class="classeBotaoEditor('code')" @click="comandoEditor('code')" title="{{ __('Código inline') }}">&lt;/&gt;</button>
        <button
            type="button"
            class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
            @click="abrirCodigoFonte()"
            title="{{ __('Código-fonte HTML') }}"
        >
            {{ __('Código') }}
        </button>
        <button
            type="button"
            class="rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100"
            @click="alternarTelaCheia()"
            title="{{ __('Tela cheia') }}"
            x-text="editorTelaCheia ? '{{ __('Sair') }}' : '{{ __('Tela cheia') }}'"
        ></button>
    </div>

    <div
        x-show="editorEstaAtivo('table')"
        x-cloak
        class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-white px-2 py-1.5"
        @mousedown.prevent
    >
        <span class="mr-1 text-xs font-medium text-gray-500">{{ __('Tabela') }}:</span>
        <button type="button" class="rounded px-2 py-1 text-xs text-gray-700 hover:bg-gray-100" @click="comandoEditor('addRowAfter')">{{ __('+ Linha') }}</button>
        <button type="button" class="rounded px-2 py-1 text-xs text-gray-700 hover:bg-gray-100" @click="comandoEditor('addColumnAfter')">{{ __('+ Coluna') }}</button>
        <button type="button" class="rounded px-2 py-1 text-xs text-gray-700 hover:bg-gray-100" @click="comandoEditor('deleteRow')">{{ __('- Linha') }}</button>
        <button type="button" class="rounded px-2 py-1 text-xs text-gray-700 hover:bg-gray-100" @click="comandoEditor('deleteColumn')">{{ __('- Coluna') }}</button>
        <button type="button" class="rounded px-2 py-1 text-xs text-gray-700 hover:bg-gray-100" @click="comandoEditor('mergeCells')">{{ __('Mesclar') }}</button>
        <button type="button" class="rounded px-2 py-1 text-xs text-gray-700 hover:bg-gray-100" @click="comandoEditor('splitCell')">{{ __('Dividir') }}</button>
        <button type="button" class="rounded px-2 py-1 text-xs text-red-600 hover:bg-red-50" @click="comandoEditor('deleteTable')">{{ __('Excluir tabela') }}</button>
    </div>

    <div
        class="editor-canvas relative max-h-[40rem] overflow-y-auto bg-stone-200/70"
        :class="editorTelaCheia ? 'max-h-none flex-1' : ''"
        @dragenter.prevent="aoArrastarSobreEditor($event)"
        @dragover.prevent="aoArrastarSobreEditor($event)"
        @dragleave="aoSairArrastoEditor($event)"
        @drop.prevent="aoSoltarNoEditor($event)"
    >
        <div
            x-show="editorArrastandoImagem || enviandoImagemConteudo"
            x-cloak
            class="pointer-events-none absolute inset-0 z-20 flex items-center justify-center bg-indigo-600/10 p-6 backdrop-blur-[1px]"
        >
            <div class="rounded-xl border-2 border-dashed border-indigo-400 bg-white/95 px-6 py-5 text-center shadow-lg">
                <p class="text-sm font-semibold text-indigo-700" x-text="enviandoImagemConteudo ? '{{ __('Enviando imagem…') }}' : '{{ __('Solte a imagem para inserir') }}'"></p>
                <p class="mt-1 text-xs text-gray-500">{{ __('JPG, PNG, WebP ou GIF') }}</p>
            </div>
        </div>

        <div class="editor-paper mx-auto my-4 min-h-[24rem] max-w-3xl bg-white px-8 py-10 shadow-sm sm:px-12">
            <div
                x-ref="editorConteudo"
                class="editor-conteudo"
                @click="garantirEditorConteudo()"
            ></div>
        </div>
    </div>
</div>

<p x-show="erroImagemConteudo && ! modalImagemAberto" x-cloak class="mt-2 text-sm text-red-600" x-text="erroImagemConteudo"></p>

{{-- Modal Inserir/Trocar imagem (estilo TinyMCE) --}}
<div
    x-show="modalImagemAberto"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4"
    @keydown.escape.window="if (modalImagemAberto) fecharModalImagem()"
>
    <div
        class="w-full max-w-xl overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl"
        @click.stop
        role="dialog"
        aria-modal="true"
        :aria-label="editorEstaAtivo('image') ? '{{ __('Trocar imagem') }}' : '{{ __('Inserir imagem') }}'"
    >
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
            <div>
                <h3 class="text-base font-semibold text-slate-900" x-text="editorEstaAtivo('image') ? '{{ __('Trocar imagem') }}' : '{{ __('Inserir imagem') }}'"></h3>
                <p class="mt-0.5 text-xs text-slate-500">{{ __('Envie do computador, arraste o arquivo ou use uma URL.') }}</p>
            </div>
            <button
                type="button"
                class="rounded-md px-2 py-1 text-sm text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                :disabled="enviandoImagemConteudo"
                @click="fecharModalImagem()"
            >
                {{ __('Fechar') }}
            </button>
        </div>

        <div class="border-b border-slate-200 px-5 pt-3">
            <div class="flex gap-1">
                <button
                    type="button"
                    class="rounded-t-md px-3 py-2 text-sm font-medium"
                    :class="modalImagemAba === 'upload' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50'"
                    @click="modalImagemAba = 'upload'; erroImagemConteudo = ''"
                >
                    {{ __('Do dispositivo') }}
                </button>
                <button
                    type="button"
                    class="rounded-t-md px-3 py-2 text-sm font-medium"
                    :class="modalImagemAba === 'url' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50'"
                    @click="modalImagemAba = 'url'; erroImagemConteudo = ''"
                >
                    {{ __('Por URL') }}
                </button>
            </div>
        </div>

        <div class="space-y-4 px-5 py-5">
            <div x-show="modalImagemAba === 'upload'" x-cloak>
                <button
                    type="button"
                    x-ref="modalImagemDropzone"
                    class="flex w-full flex-col items-center justify-center rounded-xl border-2 border-dashed px-4 py-10 text-center transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    :class="modalImagemArrastando
                        ? 'border-indigo-500 bg-indigo-50'
                        : 'border-slate-300 bg-slate-50 hover:border-indigo-400 hover:bg-indigo-50/40'"
                    :disabled="enviandoImagemConteudo"
                    @click="escolherArquivoImagem()"
                    @dragenter.prevent="aoArrastarSobreModal($event)"
                    @dragover.prevent="aoArrastarSobreModal($event)"
                    @dragleave="aoSairArrastoModal($event)"
                    @drop.prevent="aoSoltarNoModal($event)"
                >
                    <svg class="mb-3 h-10 w-10 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path d="M12 16V4"></path>
                        <path d="M8 8l4-4 4 4"></path>
                        <path d="M4 14v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"></path>
                    </svg>
                    <p class="text-sm font-semibold text-slate-800" x-text="enviandoImagemConteudo ? '{{ __('Enviando imagem…') }}' : '{{ __('Solte a imagem aqui') }}'"></p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('ou clique para escolher no computador') }}</p>
                    <p class="mt-3 text-xs text-slate-400">{{ __('JPG, PNG, WebP ou GIF') }}</p>
                </button>
            </div>

            <div x-show="modalImagemAba === 'url'" x-cloak class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="modal-imagem-url">{{ __('URL da imagem') }}</label>
                    <input
                        id="modal-imagem-url"
                        type="url"
                        x-model="modalImagemUrl"
                        placeholder="https://…"
                        class="block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        :disabled="enviandoImagemConteudo"
                        @keydown.enter.prevent="inserirImagemPorUrl()"
                    >
                </div>
                <template x-if="modalImagemUrl">
                    <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                        <img :src="modalImagemUrl" alt="" class="mx-auto max-h-48 object-contain" @@error="$el.style.display='none'" @load="$el.style.display='block'">
                    </div>
                </template>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700" for="modal-imagem-alt">{{ __('Texto alternativo') }}</label>
                <input
                    id="modal-imagem-alt"
                    type="text"
                    x-model="modalImagemAlt"
                    placeholder="{{ __('Descreva a imagem (opcional)') }}"
                    class="block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    :disabled="enviandoImagemConteudo"
                >
            </div>

            <p x-show="erroImagemConteudo" x-cloak class="text-sm text-red-600" x-text="erroImagemConteudo"></p>
        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3">
            <button
                type="button"
                class="rounded-md px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200/70"
                :disabled="enviandoImagemConteudo"
                @click="fecharModalImagem()"
            >
                {{ __('Cancelar') }}
            </button>
            <button
                type="button"
                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                x-show="modalImagemAba === 'url'"
                x-cloak
                :disabled="enviandoImagemConteudo || ! modalImagemUrl.trim()"
                @click="inserirImagemPorUrl()"
            >
                {{ __('Inserir URL') }}
            </button>
            <button
                type="button"
                class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                x-show="modalImagemAba === 'upload'"
                x-cloak
                :disabled="enviandoImagemConteudo"
                @click="escolherArquivoImagem()"
            >
                <span x-text="enviandoImagemConteudo ? '{{ __('Enviando…') }}' : '{{ __('Escolher arquivo') }}'"></span>
            </button>
        </div>
    </div>
</div>

{{-- Modal de código-fonte (como TinyMCE View Source) --}}
<div
    x-show="codigoFonteAberto"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 p-4"
    @keydown.escape.window="if (codigoFonteAberto) cancelarCodigoFonte()"
>
    <div
        class="flex h-[min(90vh,52rem)] w-full max-w-5xl flex-col overflow-hidden rounded-lg border border-slate-600 shadow-2xl"
        :class="codigoFonteTemaEscuro ? 'bg-slate-900 text-slate-100' : 'bg-white text-slate-900'"
        @click.stop
    >
        <div class="flex flex-wrap items-center gap-2 border-b px-3 py-2" :class="codigoFonteTemaEscuro ? 'border-slate-700 bg-slate-800' : 'border-slate-200 bg-slate-100'">
            <button type="button" class="rounded px-2 py-1 text-sm hover:bg-black/10" @click="formatarCodigoFonte()" title="{{ __('Formatar código') }}">{{ __('Formatar') }}</button>
            <button type="button" class="rounded px-2 py-1 text-sm hover:bg-black/10" @click="copiarCodigoFonte()" title="{{ __('Copiar código') }}">
                <span x-text="codigoFonteCopiado ? '{{ __('Copiado!') }}' : '{{ __('Copiar') }}'"></span>
            </button>
            <button type="button" class="rounded px-2 py-1 text-sm hover:bg-black/10" @click="codigoFonteTemaEscuro = ! codigoFonteTemaEscuro">
                <span x-text="codigoFonteTemaEscuro ? '{{ __('Tema claro') }}' : '{{ __('Tema escuro') }}'"></span>
            </button>
            <button type="button" class="rounded px-2 py-1 text-sm hover:bg-black/10" @click="ajustarTamanhoCodigoFonte(1)">T+</button>
            <button type="button" class="rounded px-2 py-1 text-sm hover:bg-black/10" @click="ajustarTamanhoCodigoFonte(-1)">T-</button>
            <div class="ml-auto flex items-center gap-2">
                <button type="button" class="rounded px-3 py-1.5 text-sm font-medium hover:bg-black/10" @click="cancelarCodigoFonte()">{{ __('Cancelar') }}</button>
                <button type="button" class="rounded bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500" @click="salvarCodigoFonte()">{{ __('Salvar código') }}</button>
            </div>
        </div>
        <textarea
            x-ref="codigoFonteTextarea"
            x-model="codigoFonteHtml"
            spellcheck="false"
            class="min-h-0 flex-1 w-full resize-none border-0 bg-transparent p-4 font-mono leading-relaxed focus:outline-none focus:ring-0"
            :style="`font-size: ${codigoFonteTamanho}px`"
            :class="codigoFonteTemaEscuro ? 'caret-emerald-300' : 'caret-indigo-600'"
        ></textarea>
    </div>
</div>
