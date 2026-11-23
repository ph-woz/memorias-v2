import Alpine from 'alpinejs';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    const slugFormulario = (estadoInicial = {}) => ({
        nome: estadoInicial.nome ?? '',
        identificador: estadoInicial.identificador ?? '',
        slugManual: Boolean(estadoInicial.slugManual),

        sincronizarSlug() {
            if (this.slugManual) {
                return;
            }

            this.identificador = this.gerarSlug(this.nome);
        },

        gerarSlug(valor) {
            return String(valor ?? '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        },
    });

    Alpine.data('slugFormulario', slugFormulario);

    Alpine.data('categoriaFormulario', (estadoInicial = {}) => ({
        ...slugFormulario(estadoInicial),
        fotoUrl: estadoInicial.fotoUrl ?? '',
        seoTitulo: estadoInicial.seoTitulo ?? '',
        seoDescricao: estadoInicial.seoDescricao ?? '',

        atualizarFoto(event) {
            const arquivo = event.target.files?.[0];

            if (! arquivo) {
                return;
            }

            if (this.fotoUrl && this.fotoUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.fotoUrl);
            }

            this.fotoUrl = URL.createObjectURL(arquivo);
        },
    }));

    Alpine.data('bannerFormulario', (estadoInicial = {}) => ({
        titulo: estadoInicial.titulo ?? '',
        slogan: estadoInicial.slogan ?? '',
        imagemUrl: estadoInicial.imagemUrl ?? '',
        overlayCor: estadoInicial.overlayCor ?? '#000000',
        overlayOpacidade: Number(estadoInicial.overlayOpacidade ?? 45),
        posicaoHorizontal: estadoInicial.posicaoHorizontal ?? 'esquerda',
        posicaoVertical: estadoInicial.posicaoVertical ?? 'baixo',
        ctaTexto: estadoInicial.ctaTexto ?? '',
        ctaLink: estadoInicial.ctaLink ?? '',
        ativo: estadoInicial.ativo ?? true,

        get estiloOverlay() {
            return `background-color: ${this.overlayCor}; opacity: ${this.overlayOpacidade / 100};`;
        },

        get classesPosicao() {
            const vertical = {
                topo: 'items-start',
                centro: 'items-center',
                baixo: 'items-end',
            }[this.posicaoVertical] ?? 'items-end';

            const horizontal = {
                esquerda: 'justify-start',
                centro: 'justify-center',
                direita: 'justify-end',
            }[this.posicaoHorizontal] ?? 'justify-start';

            return `${vertical} ${horizontal}`;
        },

        get classeTexto() {
            return {
                esquerda: 'text-left',
                centro: 'text-center',
                direita: 'text-right',
            }[this.posicaoHorizontal] ?? 'text-left';
        },

        atualizarImagem(event) {
            const arquivo = event.target.files?.[0];

            if (! arquivo) {
                return;
            }

            if (this.imagemUrl && this.imagemUrl.startsWith('blob:')) {
                URL.revokeObjectURL(this.imagemUrl);
            }

            this.imagemUrl = URL.createObjectURL(arquivo);
        },
    }));

    Alpine.data('bannerOrdenacao', (ordemInicial = [], urlReordenar) => ({
        ordem: [...ordemInicial],
        arrastandoId: null,
        salvando: false,
        salvo: false,
        erro: '',
        urlReordenar,

        iniciarArraste(event) {
            this.arrastandoId = Number(event.currentTarget.dataset.id);
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(this.arrastandoId));
        },

        arrastarSobre(event) {
            const alvo = event.currentTarget;
            const lista = this.$refs.lista;

            if (! this.arrastandoId || ! lista || alvo.dataset.id === String(this.arrastandoId)) {
                return;
            }

            const arrastado = lista.querySelector(`[data-id="${this.arrastandoId}"]`);

            if (! arrastado || arrastado === alvo) {
                return;
            }

            const retangulo = alvo.getBoundingClientRect();
            const antes = event.clientY < retangulo.top + retangulo.height / 2;

            lista.insertBefore(arrastado, antes ? alvo : alvo.nextSibling);
        },

        soltar() {
            // handled on dragend
        },

        async finalizarArraste() {
            this.arrastandoId = null;

            const ids = [...this.$refs.lista.querySelectorAll('[data-id]')]
                .map((item) => Number(item.dataset.id));

            if (JSON.stringify(ids) === JSON.stringify(this.ordem)) {
                return;
            }

            this.ordem = ids;
            await this.salvarOrdem();
        },

        async salvarOrdem() {
            this.salvando = true;
            this.salvo = false;
            this.erro = '';

            try {
                const resposta = await fetch(this.urlReordenar, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({ ordem: this.ordem }),
                });

                if (! resposta.ok) {
                    throw new Error('Não foi possível salvar a ordem.');
                }

                this.salvo = true;
                setTimeout(() => {
                    this.salvo = false;
                }, 1800);
            } catch (error) {
                this.erro = error.message || 'Erro ao reordenar.';
            } finally {
                this.salvando = false;
            }
        },
    }));

    Alpine.data('bannerCarousel', (total = 1) => ({
        atual: 0,
        total,
        temporizador: null,

        init() {
            if (this.total > 1) {
                this.iniciarAutoplay();
            }
        },

        iniciarAutoplay() {
            this.pararAutoplay();
            this.temporizador = setInterval(() => this.proximo(), 6000);
        },

        pararAutoplay() {
            if (this.temporizador) {
                clearInterval(this.temporizador);
                this.temporizador = null;
            }
        },

        irPara(indice) {
            this.atual = indice;
            this.iniciarAutoplay();
        },

        proximo() {
            this.atual = (this.atual + 1) % this.total;
        },

        anterior() {
            this.atual = (this.atual - 1 + this.total) % this.total;
            this.iniciarAutoplay();
        },
    }));
});

Alpine.start();
