<div class="space-y-6">
    <div>
        <x-input-label for="nome" :value="__('Nome')" />
        <x-text-input
            id="nome"
            name="nome"
            type="text"
            class="mt-1 block w-full"
            :value="old('nome', $palavraChave->nome ?? '')"
            required
            autofocus
        />
        <x-input-error class="mt-2" :messages="$errors->get('nome')" />
    </div>
</div>
