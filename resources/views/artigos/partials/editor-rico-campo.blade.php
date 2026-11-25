@php
    /** @var string $name */
    /** @var string $label */
    /** @var string $html */
    /** @var string|null $placeholder */
    /** @var string|null $minHeight */
    /** @var string|null $id */
    $placeholder = $placeholder ?? __('Escreva aqui…');
    $minHeight = $minHeight ?? '10rem';
    $id = $id ?? $name;
@endphp

<div
    class="mt-1"
    x-data="editorRicoCampo({
        campo: @js($name),
        html: @js($html),
        placeholder: @js($placeholder),
        minHeight: @js($minHeight),
    })"
>
    <input type="hidden" name="{{ $name }}" id="{{ $id }}" :value="html">

    <div class="editor-shell overflow-hidden rounded-md border border-gray-300 bg-white shadow-sm">
        <div
            class="flex flex-wrap items-center gap-1 border-b border-gray-200 bg-gray-50 px-2 py-1.5"
            @mousedown="if (! $event.target.closest('input, select, textarea, button[data-allow-focus]')) { $event.preventDefault() }"
        >
            <button type="button" :class="classeBotao('undo')" @click="comando('undo')" title="{{ __('Desfazer') }}">↩</button>
            <button type="button" :class="classeBotao('redo')" @click="comando('redo')" title="{{ __('Refazer') }}">↪</button>

            <span class="mx-1 h-5 w-px bg-gray-300" aria-hidden="true"></span>

            <button type="button" :class="classeBotao('bold')" @click="comando('bold')" title="{{ __('Negrito') }}"><span class="font-bold">B</span></button>
            <button type="button" :class="classeBotao('italic')" @click="comando('italic')" title="{{ __('Itálico') }}"><span class="italic">I</span></button>
            <button type="button" :class="classeBotao('underline')" @click="comando('underline')" title="{{ __('Sublinhado') }}"><span class="underline">U</span></button>
            <button type="button" :class="classeBotao('strike')" @click="comando('strike')" title="{{ __('Riscado') }}"><span class="line-through">S</span></button>
            <button type="button" :class="classeBotao('subscript')" @click="comando('subscript')" title="{{ __('Subscrito') }}">X₂</button>
            <button type="button" :class="classeBotao('superscript')" @click="comando('superscript')" title="{{ __('Sobrescrito') }}">X²</button>

            <span class="mx-1 h-5 w-px bg-gray-300" aria-hidden="true"></span>

            <button type="button" :class="classeBotao('bulletList')" @click="comando('bulletList')" title="{{ __('Lista') }}">•</button>
            <button type="button" :class="classeBotao('orderedList')" @click="comando('orderedList')" title="{{ __('Lista numerada') }}">1.</button>
            <button type="button" :class="classeBotao('link')" @click="comando('link')" title="{{ __('Link') }}">🔗</button>
            <button type="button" class="rounded-md px-2 py-1 text-sm text-gray-700 hover:bg-gray-100" @click="comando('unsetAllMarks')" title="{{ __('Limpar formatação') }}">✕</button>
        </div>

        <div class="editor-canvas bg-white px-3 py-3">
            <div
                x-ref="superficie"
                class="editor-conteudo editor-conteudo--compacto"
                :style="`min-height: ${minHeight}`"
                @click="garantir()"
            ></div>
        </div>
    </div>
</div>
