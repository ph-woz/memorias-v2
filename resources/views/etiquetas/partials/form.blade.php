@php
    /** @var \App\Models\Etiqueta|null $etiqueta */
    $corPadrao = old('cor', $etiqueta->cor ?? \App\Models\Etiqueta::COR_PADRAO);
    $estadoInicial = [
        'nome' => old('nome', $etiqueta->nome ?? ''),
        'slug' => old('slug', $etiqueta->slug ?? ''),
        'slugManual' => isset($etiqueta) || old('slug') !== null,
        'cor' => strtoupper((string) $corPadrao),
    ];
@endphp

<div
    class="space-y-6"
    x-data="etiquetaFormulario(@js($estadoInicial))"
>
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
        <x-input-label for="cor" :value="__('Cor')" />
        <div class="mt-1 flex items-center gap-3">
            <input
                id="cor-picker"
                type="color"
                class="h-10 w-14 cursor-pointer rounded-md border border-gray-300 bg-white p-1"
                :value="cor"
                @input="cor = $event.target.value.toUpperCase()"
            >
            <x-text-input
                id="cor"
                name="cor"
                type="text"
                class="block w-40 font-mono text-sm uppercase"
                x-model="cor"
                maxlength="7"
                pattern="^#[0-9A-Fa-f]{6}$"
                placeholder="#6366F1"
                required
            />
            <span
                class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold text-white"
                :style="`background-color: ${cor}`"
                x-text="nome || '{{ __('Pré-visualização') }}'"
            ></span>
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('cor')" />
    </div>
</div>
