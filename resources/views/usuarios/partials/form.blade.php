@php
    use App\Enums\StatusUsuario;

    /** @var \App\Models\User|null $usuario */
    /** @var list<\App\Enums\StatusUsuario> $statuses */
@endphp

<div class="space-y-6">
    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="nome" :value="__('Nome')" />
            <x-text-input
                id="nome"
                name="nome"
                type="text"
                class="mt-1 block w-full"
                :value="old('nome', $usuario->nome ?? '')"
                required
                autofocus
                autocomplete="name"
            />
            <x-input-error class="mt-2" :messages="$errors->get('nome')" />
        </div>

        <div>
            <x-input-label for="status" :value="__('Status')" />
            <select
                id="status"
                name="status"
                class="mt-1 block h-10 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                required
            >
                @foreach ($statuses as $statusOption)
                    <option
                        value="{{ $statusOption->value }}"
                        @selected((string) old('status', $usuario->status->value ?? StatusUsuario::Ativo->value) === (string) $statusOption->value)
                    >
                        {{ __($statusOption->label()) }}
                    </option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('status')" />
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="nickname" :value="__('Nickname')" />
            <x-text-input
                id="nickname"
                name="nickname"
                type="text"
                class="mt-1 block w-full"
                :value="old('nickname', $usuario->nickname ?? '')"
                required
                autocomplete="nickname"
            />
            <x-input-error class="mt-2" :messages="$errors->get('nickname')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('E-mail')" />
            <x-text-input
                id="email"
                name="email"
                type="email"
                class="mt-1 block w-full"
                :value="old('email', $usuario->email ?? '')"
                required
                autocomplete="username"
            />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />
        </div>
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="password" :value="isset($usuario) ? __('Nova senha') : __('Senha')" />
            <x-text-input
                id="password"
                name="password"
                type="password"
                class="mt-1 block w-full"
                autocomplete="new-password"
                :required="! isset($usuario)"
            />
            @if (isset($usuario))
                <p class="mt-1 text-xs text-gray-500">{{ __('Deixe em branco para manter a senha atual.') }}</p>
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmar senha')" />
            <x-text-input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                class="mt-1 block w-full"
                autocomplete="new-password"
                :required="! isset($usuario)"
            />
        </div>
    </div>

    @if (isset($usuario))
        <div>
            <x-input-label :value="__('Último acesso')" />
            <p class="mt-1 text-sm text-gray-700">
                {{ $usuario->ultimo_acesso_em?->format('d/m/Y H:i') ?? __('Nunca acessou') }}
            </p>
        </div>
    @endif
</div>
