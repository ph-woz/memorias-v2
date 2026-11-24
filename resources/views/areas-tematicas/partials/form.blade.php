@php
    /** @var \App\Models\AreaTematica|null $areaTematica */
    $estadoInicial = [
        'nome' => old('nome', $areaTematica->nome ?? ''),
        'slug' => old('slug', $areaTematica->slug ?? ''),
        // No create, o slug acompanha o nome. Na edição, preserva o slug até o usuário alterar.
        'slugManual' => isset($areaTematica) || old('slug') !== null,
    ];
@endphp

<div
    class="space-y-6"
    x-data="slugFormulario(@js($estadoInicial))"
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
</div>
