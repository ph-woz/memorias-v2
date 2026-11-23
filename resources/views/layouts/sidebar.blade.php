<aside
    class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-gray-200 bg-white transition-transform duration-200 ease-in-out lg:static lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="flex h-16 shrink-0 items-center gap-2 border-b border-gray-200 px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <x-application-logo class="block h-8 w-auto fill-current text-gray-800" />
            <span class="text-base font-semibold text-gray-900">{{ config('app.name', 'Laravel') }}</span>
        </a>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
        <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
            {{ __('Módulos') }}
        </p>

        @foreach ($sidebarItems as $item)
            <x-sidebar-link
                :href="$item->url()"
                :active="$item->isActive()"
                :icon="$item->icon"
            >
                {{ __($item->label) }}
            </x-sidebar-link>
        @endforeach
    </nav>

    <div class="border-t border-gray-200 p-3 lg:hidden">
        <button
            type="button"
            class="flex w-full items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
            @click="sidebarOpen = false"
        >
            {{ __('Fechar menu') }}
        </button>
    </div>
</aside>
