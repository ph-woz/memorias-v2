@php
    use App\Enums\PosicaoHorizontal;
    use App\Enums\PosicaoVertical;

    /** @var \App\Models\Banner|null $banner */
    $estadoInicial = [
        'titulo' => old('titulo', $banner->titulo ?? ''),
        'slogan' => old('slogan', $banner->slogan ?? ''),
        'imagemUrl' => $banner->url_imagem ?? '',
        'overlayCor' => old('overlay_cor', $banner->overlay_cor ?? '#000000'),
        'overlayOpacidade' => (int) old('overlay_opacidade', $banner->overlay_opacidade ?? 45),
        'posicaoHorizontal' => old(
            'posicao_horizontal',
            $banner->posicao_horizontal?->value ?? PosicaoHorizontal::Esquerda->value
        ),
        'posicaoVertical' => old(
            'posicao_vertical',
            $banner->posicao_vertical?->value ?? PosicaoVertical::Baixo->value
        ),
        'ctaTexto' => old('cta_texto', $banner->cta_texto ?? ''),
        'ctaLink' => old('cta_link', $banner->cta_link ?? ''),
        'ativo' => (bool) old('ativo', $banner->ativo ?? true),
    ];
@endphp

<div
    class="grid gap-8 lg:grid-cols-2"
    x-data="bannerFormulario(@js($estadoInicial))"
>
    <div class="space-y-6">
        <div>
            <x-input-label for="titulo" :value="__('Título')" />
            <x-text-input
                id="titulo"
                name="titulo"
                type="text"
                class="mt-1 block w-full"
                x-model="titulo"
                required
                autofocus
            />
            <x-input-error class="mt-2" :messages="$errors->get('titulo')" />
        </div>

        <div>
            <x-input-label for="slogan" :value="__('Slogan')" />
            <x-text-input
                id="slogan"
                name="slogan"
                type="text"
                class="mt-1 block w-full"
                x-model="slogan"
            />
            <x-input-error class="mt-2" :messages="$errors->get('slogan')" />
        </div>

        <div class="space-y-4">
            <div>
                <x-input-label for="imagem" :value="isset($banner) ? __('Trocar imagem') : __('Imagem')" />
                <input
                    id="imagem"
                    name="imagem"
                    type="file"
                    accept="image/*"
                    class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                    @change="atualizarImagem($event)"
                    @unless(isset($banner)) required @endunless
                />
                <p class="mt-1 text-xs text-gray-500">{{ __('JPG, PNG ou WEBP até 5MB.') }}</p>
                <x-input-error class="mt-2" :messages="$errors->get('imagem')" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label for="overlay_cor" :value="__('Cor do overlay')" />
                    <div class="mt-1 flex items-center gap-3">
                        <input
                            id="overlay_cor"
                            name="overlay_cor"
                            type="color"
                            class="h-10 w-14 cursor-pointer rounded border border-gray-300 bg-white p-1 shadow-sm"
                            x-model="overlayCor"
                        >
                        <span class="text-sm text-gray-600" x-text="overlayCor"></span>
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('overlay_cor')" />
                </div>

                <div>
                    <x-input-label for="overlay_opacidade" :value="__('Opacidade do overlay')" />
                    <div class="mt-1 flex items-center gap-3">
                        <input
                            id="overlay_opacidade"
                            name="overlay_opacidade"
                            type="range"
                            min="0"
                            max="100"
                            step="1"
                            class="w-full"
                            x-model.number="overlayOpacidade"
                        >
                        <span class="w-12 text-right text-sm text-gray-600" x-text="`${overlayOpacidade}%`"></span>
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('overlay_opacidade')" />
                </div>
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="cta_texto" :value="__('Texto do CTA')" />
                <x-text-input
                    id="cta_texto"
                    name="cta_texto"
                    type="text"
                    class="mt-1 block w-full"
                    x-model="ctaTexto"
                />
                <x-input-error class="mt-2" :messages="$errors->get('cta_texto')" />
            </div>

            <div>
                <x-input-label for="cta_link" :value="__('Link do CTA')" />
                <x-text-input
                    id="cta_link"
                    name="cta_link"
                    type="url"
                    class="mt-1 block w-full"
                    x-model="ctaLink"
                    placeholder="https://"
                />
                <x-input-error class="mt-2" :messages="$errors->get('cta_link')" />
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="posicao_horizontal" :value="__('Posição horizontal')" />
                <select
                    id="posicao_horizontal"
                    name="posicao_horizontal"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    x-model="posicaoHorizontal"
                >
                    @foreach (PosicaoHorizontal::cases() as $posicao)
                        <option value="{{ $posicao->value }}">{{ __($posicao->label()) }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('posicao_horizontal')" />
            </div>

            <div>
                <x-input-label for="posicao_vertical" :value="__('Posição vertical')" />
                <select
                    id="posicao_vertical"
                    name="posicao_vertical"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    x-model="posicaoVertical"
                >
                    @foreach (PosicaoVertical::cases() as $posicao)
                        <option value="{{ $posicao->value }}">{{ __($posicao->label()) }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('posicao_vertical')" />
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input
                type="checkbox"
                name="ativo"
                value="1"
                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                x-model="ativo"
            >
            <span>{{ __('Ativo na home') }}</span>
        </label>
    </div>

    <div>
        <div class="mb-3 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800">{{ __('Preview') }}</h3>
            <span class="text-xs text-gray-400">{{ __('Atualiza em tempo real') }}</span>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-gray-900 shadow-sm">
            <div class="relative min-h-[280px] overflow-hidden sm:min-h-[340px]">
                <template x-if="imagemUrl">
                    <img :src="imagemUrl" :alt="titulo" class="absolute inset-0 h-full w-full object-cover">
                </template>
                <div
                    x-show="overlayOpacidade > 0"
                    class="absolute inset-0"
                    :style="estiloOverlay"
                ></div>
                <div
                    class="relative z-10 flex min-h-[280px] w-full sm:min-h-[340px]"
                    :class="classesPosicao"
                >
                    <div class="w-full max-w-xl px-6 py-8 text-white" :class="classeTexto">
                        <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl" x-text="titulo || 'Título do banner'"></h2>
                        <p class="mt-2 text-sm text-white/85 sm:text-base" x-show="slogan" x-text="slogan"></p>
                        <div class="mt-5" x-show="ctaTexto && ctaLink">
                            <span class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-stone-900" x-text="ctaTexto"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
