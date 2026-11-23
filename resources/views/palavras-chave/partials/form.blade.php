@php
    /** @var \App\Models\PalavraChave|null $palavraChave */
    $estadoInicial = [
        'nome' => old('nome', $palavraChave->nome ?? ''),
        'identificador' => old('identificador', $palavraChave->identificador ?? ''),
        // No create, o slug acompanha o nome. Na edição, preserva o slug até o usuário alterar.
        'slugManual' => isset($palavraChave) || old('identificador') !== null,
    ];
@endphp

<div
    class="space-y-6"
    x-data="slugFormulario(@js($estadoInicial))"
>
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
        <p class="mt-1 text-xs text-gray-500">{{ __('Gerado automaticamente a partir do nome. Você pode ajustar se quiser.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('identificador')" />
    </div>
</div>
