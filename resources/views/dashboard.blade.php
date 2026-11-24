<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="truncate text-lg font-semibold leading-tight text-gray-800">
                {{ __('Dashboard') }}
            </h2>
            <p class="mt-0.5 text-sm text-gray-500">
                {{ $periodo->rotulo }}
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-teal-900 via-teal-800 to-stone-900 px-6 py-7 text-white shadow-sm sm:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-sm font-medium text-teal-100/80">{{ __('Painel editorial') }}</p>
                        <h3 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">
                            {{ __('Olá, :nome', ['nome' => auth()->user()->nome]) }}
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-teal-50/80">
                            {{ __('Acompanhe o ritmo das publicações, o que pede atenção e os atalhos para continuar o trabalho.') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('artigos.create') }}" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-teal-900 hover:bg-teal-50">
                            {{ __('Novo artigo') }}
                        </a>
                        <a href="{{ route('banners.create') }}" class="rounded-md bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/25 hover:bg-white/15">
                            {{ __('Novo banner') }}
                        </a>
                        <a href="{{ route('home') }}" target="_blank" class="rounded-md bg-white/10 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/25 hover:bg-white/15">
                            {{ __('Ver site') }}
                        </a>
                    </div>
                </div>
            </section>

            @php
                $classeBotaoPeriodo = 'inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-medium transition';
                $classeBotaoPeriodoAtivo = 'border-slate-900 bg-slate-900 text-white';
                $classeBotaoPeriodoInativo = 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50';
            @endphp

            <section
                class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200"
                x-data="{ mesesAberto: false, personalizadoAberto: {{ $periodo->estaAtivo(\App\Support\DashboardPeriodo::PERSONALIZADO) ? 'true' : 'false' }} }"
                @keydown.escape.window="mesesAberto = false"
            >
                <div class="flex flex-col gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                                <path d="M3 10h18"></path>
                                <path d="M8 3v4"></path>
                                <path d="M16 3v4"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">{{ __('Período') }}</p>
                            <p class="text-base font-semibold text-slate-900">{{ $periodo->rotulo }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            href="{{ $periodo->url(['periodo' => \App\Support\DashboardPeriodo::HOJE]) }}"
                            @class([$classeBotaoPeriodo, $periodo->estaAtivo(\App\Support\DashboardPeriodo::HOJE) ? $classeBotaoPeriodoAtivo : $classeBotaoPeriodoInativo])
                        >
                            {{ __('Hoje') }}
                        </a>

                        <div class="relative">
                            <button
                                type="button"
                                @click="mesesAberto = ! mesesAberto; personalizadoAberto = false"
                                @class([$classeBotaoPeriodo, $periodo->estaAtivo(\App\Support\DashboardPeriodo::MES) ? $classeBotaoPeriodoAtivo : $classeBotaoPeriodoInativo])
                            >
                                {{ $periodo->rotuloBotaoMes }}
                                <svg class="h-3.5 w-3.5 opacity-70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                                </svg>
                            </button>

                            <div
                                x-show="mesesAberto"
                                x-cloak
                                @click.outside="mesesAberto = false"
                                class="absolute left-0 z-20 mt-2 max-h-64 w-52 overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-lg"
                            >
                                @foreach ($periodo->mesesOpcoes as $mesOpcao)
                                    <a
                                        href="{{ $periodo->url(['periodo' => \App\Support\DashboardPeriodo::MES, 'mes' => $mesOpcao['valor']]) }}"
                                        @class([
                                            'block px-3 py-2 text-sm',
                                            'bg-slate-100 font-semibold text-slate-900' => $periodo->mesRef === $mesOpcao['valor'] && $periodo->estaAtivo(\App\Support\DashboardPeriodo::MES),
                                            'text-slate-700 hover:bg-slate-50' => ! ($periodo->mesRef === $mesOpcao['valor'] && $periodo->estaAtivo(\App\Support\DashboardPeriodo::MES)),
                                        ])
                                    >
                                        {{ $mesOpcao['rotulo'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        <a
                            href="{{ $periodo->url(['periodo' => \App\Support\DashboardPeriodo::ULTIMOS_7]) }}"
                            @class([$classeBotaoPeriodo, $periodo->estaAtivo(\App\Support\DashboardPeriodo::ULTIMOS_7) ? $classeBotaoPeriodoAtivo : $classeBotaoPeriodoInativo])
                        >
                            {{ __('Últimos 7 dias') }}
                        </a>
                        <a
                            href="{{ $periodo->url(['periodo' => \App\Support\DashboardPeriodo::ULTIMOS_30]) }}"
                            @class([$classeBotaoPeriodo, $periodo->estaAtivo(\App\Support\DashboardPeriodo::ULTIMOS_30) ? $classeBotaoPeriodoAtivo : $classeBotaoPeriodoInativo])
                        >
                            {{ __('Últimos 30 dias') }}
                        </a>
                        <a
                            href="{{ $periodo->url(['periodo' => \App\Support\DashboardPeriodo::ESTE_MES]) }}"
                            @class([$classeBotaoPeriodo, $periodo->estaAtivo(\App\Support\DashboardPeriodo::ESTE_MES) ? $classeBotaoPeriodoAtivo : $classeBotaoPeriodoInativo])
                        >
                            {{ __('Este mês') }}
                        </a>
                        <a
                            href="{{ $periodo->url(['periodo' => \App\Support\DashboardPeriodo::MES_PASSADO]) }}"
                            @class([$classeBotaoPeriodo, $periodo->estaAtivo(\App\Support\DashboardPeriodo::MES_PASSADO) ? $classeBotaoPeriodoAtivo : $classeBotaoPeriodoInativo])
                        >
                            {{ __('Mês passado') }}
                        </a>
                        <button
                            type="button"
                            @click="personalizadoAberto = ! personalizadoAberto; mesesAberto = false"
                            @class([$classeBotaoPeriodo, $periodo->estaAtivo(\App\Support\DashboardPeriodo::PERSONALIZADO) ? $classeBotaoPeriodoAtivo : $classeBotaoPeriodoInativo])
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="3" y="5" width="18" height="16" rx="2"></rect>
                                <path d="M3 10h18"></path>
                            </svg>
                            {{ __('Personalizado') }}
                        </button>
                    </div>

                    <form
                        x-show="personalizadoAberto"
                        x-cloak
                        method="GET"
                        action="{{ route('dashboard') }}"
                        class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3"
                    >
                        <input type="hidden" name="periodo" value="{{ \App\Support\DashboardPeriodo::PERSONALIZADO }}">
                        <div>
                            <label for="periodo-inicio" class="mb-1 block text-xs font-medium text-slate-500">{{ __('De') }}</label>
                            <input
                                id="periodo-inicio"
                                type="date"
                                name="inicio"
                                value="{{ $periodo->inicio->toDateString() }}"
                                class="rounded-md border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                required
                            >
                        </div>
                        <div>
                            <label for="periodo-fim" class="mb-1 block text-xs font-medium text-slate-500">{{ __('Até') }}</label>
                            <input
                                id="periodo-fim"
                                type="date"
                                name="fim"
                                value="{{ $periodo->fim->toDateString() }}"
                                class="rounded-md border-slate-300 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500"
                                required
                            >
                        </div>
                        <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            {{ __('Aplicar') }}
                        </button>
                    </form>
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-2">
                <a href="{{ route('artigos.index', ['status' => 'publicados']) }}" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition hover:ring-teal-300">
                    <p class="text-sm text-gray-500">{{ __('Publicados') }}</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ $totais['publicados'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ __(':total atualizados no período', ['total' => $totais['artigos']]) }}</p>
                </a>
                <a href="{{ route('artigos.index', ['status' => 'rascunhos']) }}" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 transition hover:ring-teal-300">
                    <p class="text-sm text-gray-500">{{ __('Rascunhos') }}</p>
                    <p class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ $totais['rascunhos'] }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ __('Atualizados no período') }}</p>
                </a>
            </section>

            <section class="grid gap-6 lg:grid-cols-5">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200 lg:col-span-3">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-base font-semibold text-gray-900">{{ __('Artigos recentes') }}</h3>
                        <a href="{{ route('artigos.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Ver todos') }}</a>
                    </div>

                    <ul class="mt-4 divide-y divide-gray-100">
                        @forelse ($artigosRecentes as $artigo)
                            <li class="flex items-center gap-3 py-3">
                                @if ($artigo->url_foto)
                                    <img src="{{ $artigo->url_foto }}" alt="" class="h-12 w-12 shrink-0 rounded-lg object-cover">
                                @else
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-stone-100 text-xs text-stone-400">—</div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('artigos.edit', $artigo) }}" class="block truncate text-sm font-medium text-gray-900 hover:text-teal-800">
                                        {{ $artigo->titulo }}
                                    </a>
                                    <p class="mt-0.5 truncate text-xs text-gray-500">
                                        {{ $artigo->categorias->pluck('nome')->join(', ') ?: __('Sem categoria') }}
                                        · {{ $artigo->updated_at?->diffForHumans() }}
                                    </p>
                                </div>
                                <span @class([
                                    'rounded-md px-2 py-1 text-[11px] font-semibold uppercase tracking-wide',
                                    'bg-teal-50 text-teal-800' => $artigo->status === \App\Enums\StatusArtigo::Publicado,
                                    'bg-amber-50 text-amber-800' => $artigo->status === \App\Enums\StatusArtigo::Rascunho,
                                ])>
                                    {{ $artigo->status->label() }}
                                </span>
                            </li>
                        @empty
                            <li class="py-8 text-center text-sm text-gray-500">{{ __('Nenhum artigo neste período.') }}</li>
                        @endforelse
                    </ul>
                </div>

                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                        <h3 class="text-base font-semibold text-gray-900">{{ __('Precisa de atenção') }}</h3>
                        <ul class="mt-4 space-y-1 text-sm">
                            <li>
                                <a
                                    href="{{ route('artigos.index', ['atencao' => 'sem_foto']) }}"
                                    class="flex items-center justify-between gap-3 rounded-lg px-2 py-2 text-gray-600 transition hover:bg-stone-50 hover:text-teal-800"
                                >
                                    <span>{{ __('Sem foto') }}</span>
                                    <span class="font-semibold text-gray-900">{{ $atencao['sem_foto'] }}</span>
                                </a>
                            </li>
                            <li>
                                <a
                                    href="{{ route('artigos.index', ['atencao' => 'sem_categoria']) }}"
                                    class="flex items-center justify-between gap-3 rounded-lg px-2 py-2 text-gray-600 transition hover:bg-stone-50 hover:text-teal-800"
                                >
                                    <span>{{ __('Sem categoria') }}</span>
                                    <span class="font-semibold text-gray-900">{{ $atencao['sem_categoria'] }}</span>
                                </a>
                            </li>
                            <li>
                                <a
                                    href="{{ route('artigos.index', ['atencao' => 'rascunhos_antigos', 'status' => 'rascunhos']) }}"
                                    class="flex items-center justify-between gap-3 rounded-lg px-2 py-2 text-gray-600 transition hover:bg-stone-50 hover:text-teal-800"
                                >
                                    <span>{{ __('Rascunhos parados (+14 dias)') }}</span>
                                    <span class="font-semibold text-gray-900">{{ $atencao['rascunhos_antigos'] }}</span>
                                </a>
                            </li>
                            <li class="border-t border-gray-100 pt-1">
                                <a
                                    href="{{ route('artigos.index', ['status' => 'excluidos', 'atencao' => 'excluidos']) }}"
                                    class="flex items-center justify-between gap-3 rounded-lg px-2 py-2 text-gray-600 transition hover:bg-stone-50 hover:text-teal-800"
                                >
                                    <span>{{ __('Na lixeira') }}</span>
                                    <span class="font-semibold text-teal-700">{{ $totais['excluidos'] }}</span>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-base font-semibold text-gray-900">{{ __('Etiquetas em uso') }}</h3>
                            <a href="{{ route('etiquetas.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Gerenciar') }}</a>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @forelse ($etiquetasPopulares as $etiqueta)
                                <span
                                    class="inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-medium text-white"
                                    style="background-color: {{ $etiqueta->cor }}"
                                >
                                    {{ $etiqueta->nome }}
                                    <span class="rounded bg-black/15 px-1.5 py-0.5">{{ $etiqueta->artigos_count }}</span>
                                </span>
                            @empty
                                <p class="w-full py-6 text-center text-sm text-gray-500">{{ __('Nenhuma etiqueta no período.') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-200">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-gray-900">{{ __('Por categoria') }}</h3>
                    <a href="{{ route('categorias.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-800">{{ __('Gerenciar') }}</a>
                </div>
                <ul class="mt-4 space-y-3">
                    @forelse ($porCategoria as $categoria)
                        <li>
                            <div class="mb-1 flex items-center justify-between gap-3 text-sm">
                                <span class="truncate font-medium text-gray-800">{{ $categoria->nome }}</span>
                                <span class="text-gray-500">{{ $categoria->total_artigos }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-stone-100">
                                <div
                                    class="h-full rounded-full bg-teal-600"
                                    style="width: {{ max(4, (int) round(($categoria->total_artigos / $maxCategoria) * 100)) }}%"
                                ></div>
                            </div>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-gray-500">{{ __('Nenhuma categoria no período.') }}</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-app-layout>
