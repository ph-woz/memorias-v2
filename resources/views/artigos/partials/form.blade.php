@php
    /** @var \App\Models\Artigo|null $artigo */
    $autorIdsPadrao = isset($artigo)
        ? $artigo->autores->pluck('id')->all()
        : [auth()->id()];
    $autoresSelecionados = old('autor_ids', $autorIdsPadrao);
    $palavrasChaveSelecionadas = old('palavra_chave_ids', isset($artigo) ? $artigo->palavrasChave->pluck('id')->all() : []);
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="titulo" :value="__('Título')" />
        <x-text-input
            id="titulo"
            name="titulo"
            type="text"
            class="mt-1 block w-full"
            :value="old('titulo', $artigo->titulo ?? '')"
            required
            autofocus
        />
        <x-input-error class="mt-2" :messages="$errors->get('titulo')" />
    </div>

    <div>
        <x-input-label for="resumo" :value="__('Resumo')" />
        <textarea
            id="resumo"
            name="resumo"
            rows="3"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        >{{ old('resumo', $artigo->resumo ?? '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('resumo')" />
    </div>

    <div>
        <x-input-label for="conteudo" :value="__('Conteúdo')" />
        <textarea
            id="conteudo"
            name="conteudo"
            rows="12"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
        >{{ old('conteudo', $artigo->conteudo ?? '') }}</textarea>
        <x-input-error class="mt-2" :messages="$errors->get('conteudo')" />
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="categoria_id" :value="__('Categoria')" />
            <select
                id="categoria_id"
                name="categoria_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">{{ __('Sem categoria') }}</option>
                @foreach ($categorias as $categoria)
                    <option
                        value="{{ $categoria->id }}"
                        @selected((string) old('categoria_id', $artigo->categoria_id ?? '') === (string) $categoria->id)
                    >
                        {{ $categoria->nome }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('categoria_id')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select
                id="status"
                name="status"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
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
    </div>

    <div>
        <x-input-label for="publicado_em" :value="__('Data de publicação')" />
        <x-text-input
            id="publicado_em"
            name="publicado_em"
            type="datetime-local"
            class="mt-1 block w-full"
            :value="old('publicado_em', isset($artigo) && $artigo->publicado_em ? $artigo->publicado_em->format('Y-m-d\\TH:i') : '')"
        />
        <p class="mt-1 text-xs text-gray-500">{{ __('Se publicar sem data, usa o horário atual.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('publicado_em')" />
    </div>

    <div>
        <x-input-label :value="__('Autores')" />
        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($autores as $autor)
                <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        name="autor_ids[]"
                        value="{{ $autor->id }}"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        @checked(in_array($autor->id, array_map('intval', (array) $autoresSelecionados), true))
                    >
                    <span>
                        {{ $autor->nome }}
                        <span class="text-xs text-gray-400">{{ '@'.$autor->nickname }}</span>
                    </span>
                </label>
            @empty
                <p class="text-sm text-gray-500">{{ __('Nenhum autor disponível.') }}</p>
            @endforelse
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('autor_ids')" />
    </div>

    <div>
        <x-input-label :value="__('Palavras-chave')" />
        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($palavrasChave as $palavraChave)
                <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        name="palavra_chave_ids[]"
                        value="{{ $palavraChave->id }}"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                        @checked(in_array($palavraChave->id, array_map('intval', (array) $palavrasChaveSelecionadas), true))
                    >
                    <span>{{ $palavraChave->nome }}</span>
                </label>
            @empty
                <p class="text-sm text-gray-500">{{ __('Nenhuma palavra-chave cadastrada.') }}</p>
            @endforelse
        </div>
        <x-input-error class="mt-2" :messages="$errors->get('palavra_chave_ids')" />
    </div>
</div>
