@php
    $marca = 'Memórias';
    $headerSobreImagem = $headerSobreImagem ?? false;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', $marca)</title>
        @hasSection('meta')
            @yield('meta')
        @endif

        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/vnd.microsoft.icon">
        <link rel="apple-touch-icon-precomposed" href="{{ asset('apple-touch-icon.png') }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|newsreader:400,500,600,700,400i,600i&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body
        class="site-shell site-grain min-h-screen font-sans text-ink antialiased"
        x-data="{ scrolled: false }"
        @scroll.window="scrolled = window.scrollY > 40"
    >
        <header
            class="fixed inset-x-0 top-0 z-40 transition-all duration-500 ease-editorial"
            :class="scrolled
                ? 'site-header-scrolled'
                : '{{ $headerSobreImagem ? 'bg-gradient-to-b from-ink/70 via-ink/25 to-transparent text-white' : 'bg-transparent text-ink' }}'"
        >
            <div class="mx-auto flex max-w-site items-center justify-between gap-6 px-5 py-5 sm:px-8 lg:px-10">
                <a
                    href="{{ route('home') }}"
                    class="group flex flex-col transition"
                    :class="scrolled
                        ? 'text-ink'
                        : '{{ $headerSobreImagem ? 'text-white' : 'text-ink' }}'"
                >
                    <span class="font-display text-2xl font-semibold tracking-tight sm:text-[1.75rem]">
                        {{ $marca }}
                    </span>
                    <span class="mt-0.5 text-[0.65rem] font-medium uppercase tracking-[0.22em] opacity-70 transition group-hover:opacity-100">
                        Instituto Oswaldo Cruz
                    </span>
                </a>

                <div class="flex items-center gap-2 sm:gap-3">
                    <x-site.busca-form
                        :termo="request('q', '')"
                        :sobre-imagem="$headerSobreImagem"
                        variante="header"
                    />
                    <x-site.menu :itens="$menuItens" :sobre-imagem="$headerSobreImagem" />
                    <x-idioma-switcher variante="site" :sobre-imagem="$headerSobreImagem" />
                </div>
            </div>
        </header>

        @yield('content')

        <footer class="relative mt-24 overflow-hidden border-t border-ink/10 bg-ink text-paper">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,rgba(45,212,191,0.12),transparent_45%)]"></div>
            <div class="relative mx-auto max-w-site px-5 py-16 sm:px-8 lg:px-10">
                <div class="grid gap-12 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <p class="font-display text-3xl font-semibold tracking-tight">{{ $marca }}</p>
                        <p class="mt-1 text-[0.65rem] font-medium uppercase tracking-[0.2em] text-accent-bright/80">
                            Instituto Oswaldo Cruz
                        </p>
                        <p class="mt-4 max-w-sm text-sm leading-relaxed text-paper/65">
                            {{ __('Revista científica — microbiologia, vetores e infecções humanas.') }}
                        </p>
                        <div class="mt-8 space-y-3 text-sm text-paper/70">
                            <p class="font-medium text-paper">Memórias do Instituto Oswaldo Cruz</p>
                            <p>
                                Av. Brasil 4365, Castelo Mourisco<br>
                                sala 201, Manguinhos, 21040-900<br>
                                Rio de Janeiro, RJ, Brazil
                            </p>
                            <p>Tel.: +55-21-2562-1222</p>
                            <p>
                                <a href="mailto:memorias@fiocruz.br" class="text-accent-bright transition hover:text-white">
                                    memorias@fiocruz.br
                                </a>
                            </p>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-accent-bright">
                            {{ __('Programa de apoio') }}
                        </h2>
                        <div class="mt-6 flex flex-col gap-5">
                            {{-- Logos oficiais escuros: placa clara para contraste e preservar a bandeira. --}}
                            <div class="space-y-4 bg-paper px-4 py-4">
                                <img
                                    src="{{ asset('images/footer/logo-ioc.png') }}"
                                    alt="Instituto Oswaldo Cruz"
                                    width="150"
                                    height="48"
                                    class="h-10 w-auto"
                                >
                                <img
                                    src="{{ asset('images/footer/logo-governo-federal.png') }}"
                                    alt="Fiocruz, SUS e Ministério da Saúde"
                                    width="198"
                                    height="109"
                                    class="h-auto w-full max-w-[13rem]"
                                >
                                <div class="flex flex-wrap items-center gap-4 border-t border-ink/10 pt-4">
                                    <img src="{{ asset('images/footer/logo-faperj.png') }}" alt="FAPERJ" width="95" height="40" class="h-9 w-auto">
                                    <img src="{{ asset('images/footer/logo-cnpq.png') }}" alt="CNPq" width="94" height="40" class="h-9 w-auto">
                                    <img src="{{ asset('images/footer/logo-capes.png') }}" alt="CAPES" width="73" height="44" class="h-10 w-auto">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-accent-bright">
                            {{ __('Mapa do site') }}
                        </h2>
                        <ul class="mt-6 columns-1 gap-x-8 space-y-2.5 text-sm text-paper/75 sm:columns-2">
                            @foreach ([
                                ['Conteúdo', 'https://memorias.ioc.fiocruz.br/content'],
                                ['Instruções aos autores', 'https://memorias.ioc.fiocruz.br/instructions-to-authors'],
                                ['Política editorial', 'https://memorias.ioc.fiocruz.br/editorial-policy'],
                                ['Submissão online', 'https://mc04.manuscriptcentral.com/mioc-scielo'],
                                ['Conselho Memórias', 'https://memorias.ioc.fiocruz.br/memorias-board'],
                                ['Fast Track', 'https://memorias.ioc.fiocruz.br/fast-track'],
                                ['115 YEARS', 'https://memorias.ioc.fiocruz.br/115yearsmain'],
                                ['125 IOC', 'https://memorias.ioc.fiocruz.br/125yearsioc'],
                            ] as [$label, $href])
                                <li>
                                    <a href="{{ $href }}" class="transition hover:text-white" target="_blank" rel="noopener noreferrer">
                                        {{ __($label) }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="mt-14 flex flex-col items-start justify-between gap-8 border-t border-white/10 pt-8 sm:flex-row sm:items-center">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ([
                            ['https://twitter.com/MemoriasJournal', 'X', 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.227-8.739L1.254 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z'],
                            ['https://www.facebook.com/MemoriasJournal/', 'Facebook', 'M22 12.06C22 6.505 17.523 2 12 2S2 6.505 2 12.06c0 5.022 3.657 9.184 8.438 9.94v-7.03H7.898v-2.91h2.54V9.845c0-2.517 1.492-3.91 3.777-3.91 1.094 0 2.238.197 2.238.197v2.476h-1.26c-1.243 0-1.63.775-1.63 1.57v1.888h2.773l-.443 2.91h-2.33V22c4.78-.756 8.437-4.918 8.437-9.94z'],
                            ['https://br.pinterest.com/MemoriasJournal/', 'Pinterest', 'M12.017 2C6.62 2 3.133 5.66 3.133 9.982c0 2.634 1.47 4.7 3.82 5.52-.053-.47-.1-1.19.02-1.703.11-.47.708-3.01.708-3.01s-.18-.36-.18-.894c0-.837.486-1.462 1.09-1.462.514 0 .763.386.763.848 0 .517-.33 1.29-.5 2.006-.142.6.3 1.09.89 1.09 1.07 0 1.89-1.13 1.89-2.76 0-1.44-.103-2.56-2.48-2.56-1.69 0-2.68 1.27-2.68 2.58 0 .51.2 1.06.44 1.36a.17.17 0 0 1 .04.16c-.04.18-.14.56-.16.64-.03.1-.09.12-.21.07-1.04-.48-1.69-2-1.69-3.22 0-2.62 1.9-5.03 5.49-5.03 2.88 0 5.12 2.05 5.12 4.79 0 2.86-1.8 5.16-4.3 5.16-.84 0-1.63-.44-1.9-.95l-.52 1.97c-.19.73-.7 1.65-1.04 2.21A9.87 9.87 0 0 0 12.017 22C17.4 22 21 17.52 21 12S16.4 2 12.017 2z'],
                            ['https://www.instagram.com/memoriasjournal/', 'Instagram', 'M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5A4.25 4.25 0 0 0 7.75 20.5h8.5a4.25 4.25 0 0 0 4.25-4.25v-8.5A4.25 4.25 0 0 0 16.25 3.5h-8.5zm8.75 1.75a1 1 0 1 1 0 2 1 1 0 0 1 0-2zM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 1.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z'],
                            ['https://www.linkedin.com/company/revista-memorias-do-ioc', 'LinkedIn', 'M4.98 3.5C4.98 4.88 3.86 6 2.5 6S.02 4.88.02 3.5 1.14 1 2.5 1s2.48 1.12 2.48 2.5zM.22 8.5h4.56V23H.22V8.5zM8.34 8.5h4.37v1.98h.06c.61-1.16 2.1-2.38 4.32-2.38 4.62 0 5.47 3.04 5.47 7v7.9h-4.56v-7c0-1.67-.03-3.81-2.32-3.81-2.32 0-2.68 1.81-2.68 3.69V23H8.34V8.5z'],
                        ] as [$href, $label, $path])
                            <a
                                href="{{ $href }}"
                                class="inline-flex size-10 items-center justify-center border border-white/15 text-paper/80 transition hover:border-accent-bright hover:text-accent-bright"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="{{ $label }}"
                            >
                                <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $path }}"/></svg>
                            </a>
                        @endforeach
                    </div>

                    <p class="max-w-md text-xs leading-relaxed text-paper/45">
                        Copyright &copy;
                        <strong class="font-medium text-paper/70">Instituto Oswaldo Cruz - Fundação Oswaldo Cruz</strong>
                        - Ministério da Saúde {{ now()->year }}
                    </p>
                </div>
            </div>
        </footer>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const nodes = document.querySelectorAll('.reveal-on-scroll');
                if (! nodes.length || ! ('IntersectionObserver' in window)) {
                    nodes.forEach((el) => el.classList.add('is-visible'));
                    return;
                }
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('is-visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.12, rootMargin: '0px 0px -8% 0px' });
                nodes.forEach((el) => observer.observe(el));
            });
        </script>
    </body>
</html>
