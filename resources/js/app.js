import Alpine from 'alpinejs';
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { Color } from '@tiptap/extension-color';
import FontFamily from '@tiptap/extension-font-family';
import Highlight from '@tiptap/extension-highlight';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import TaskItem from '@tiptap/extension-task-item';
import TaskList from '@tiptap/extension-task-list';
import { Table } from '@tiptap/extension-table';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TableRow from '@tiptap/extension-table-row';
import TextAlign from '@tiptap/extension-text-align';
import { TextStyle } from '@tiptap/extension-text-style';
import Underline from '@tiptap/extension-underline';
import { FontSize } from './editor/font-size';
import { formatarHtml } from './editor/format-html';
import { RichImage } from './editor/rich-image';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    const slugFormulario = (estadoInicial = {}) => ({
        nome: estadoInicial.nome ?? '',
        slug: estadoInicial.slug ?? '',
        slugManual: Boolean(estadoInicial.slugManual),

        sincronizarSlug() {
            if (this.slugManual) {
                return;
            }

            this.slug = this.gerarSlug(this.nome);
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

    Alpine.data('etiquetaFormulario', (estadoInicial = {}) => ({
        ...slugFormulario(estadoInicial),
        cor: String(estadoInicial.cor ?? '#6366F1').toUpperCase(),
    }));

    Alpine.data('filtroMultiSelect', (estadoInicial = {}) => ({
        aberto: false,
        busca: '',
        rotulo: estadoInicial.rotulo ?? 'Selecionar',
        name: estadoInicial.name ?? 'ids[]',
        opcoes: [...(estadoInicial.opcoes ?? [])].map((opcao) => ({
            id: String(opcao.id ?? ''),
            nome: String(opcao.nome ?? ''),
        })).filter((opcao) => opcao.id !== ''),
        selecionados: [...(estadoInicial.selecionados ?? [])].map((id) => String(id)),

        get rotuloBotao() {
            if (this.selecionados.length === 0) {
                return this.rotulo;
            }

            if (this.selecionados.length === 1) {
                return this.opcoes.find((opcao) => opcao.id === this.selecionados[0])?.nome
                    ?? this.rotulo;
            }

            return `${this.selecionados.length} selecionadas`;
        },

        get opcoesFiltradas() {
            const busca = String(this.busca ?? '').trim().toLowerCase();

            if (busca === '') {
                return this.opcoes;
            }

            return this.opcoes.filter((opcao) => opcao.nome.toLowerCase().includes(busca));
        },

        estaSelecionado(id) {
            return this.selecionados.includes(String(id));
        },

        alternar(id) {
            const chave = String(id);

            if (this.selecionados.includes(chave)) {
                this.selecionados = this.selecionados.filter((atual) => atual !== chave);

                return;
            }

            this.selecionados = [...this.selecionados, chave];
        },

        limpar() {
            this.selecionados = [];
        },

        abrir() {
            this.aberto = true;
            this.$nextTick(() => this.$refs.buscaInput?.focus());
        },

        fechar() {
            this.aberto = false;
            this.busca = '';
        },

        alternarPainel() {
            if (this.aberto) {
                this.fechar();

                return;
            }

            this.abrir();
        },
    }));

    const fotoCropFormulario = (estadoInicial = {}) => ({
        fotoUrl: estadoInicial.fotoUrl ?? '',
        cropAberto: false,
        cropSrc: '',
        zoom: 1,
        offsetX: 0,
        offsetY: 0,
        baseScale: 1,
        imagemNaturalLargura: 0,
        imagemNaturalAltura: 0,
        arrastando: false,
        arrasteInicioX: 0,
        arrasteInicioY: 0,
        offsetInicioX: 0,
        offsetInicioY: 0,
        processandoCrop: false,
        viewport: 288,
        saida: 1200,
        qualidadeWebp: 0.82,
        backupFotoFiles: null,
        nomeArquivoCrop: estadoInicial.nomeArquivoCrop ?? 'foto.webp',

        get estiloCropImg() {
            const escala = this.baseScale * this.zoom;

            return {
                width: `${this.imagemNaturalLargura * escala}px`,
                height: `${this.imagemNaturalAltura * escala}px`,
                transform: `translate(${this.offsetX}px, ${this.offsetY}px)`,
            };
        },

        prepararSelecao() {
            this.backupFotoFiles = this.$refs.fotoInput?.files ?? null;
        },

        abrirCrop(event) {
            const arquivo = event.target.files?.[0];

            if (! arquivo) {
                return;
            }

            if (this.cropSrc && this.cropSrc.startsWith('blob:')) {
                URL.revokeObjectURL(this.cropSrc);
            }

            this.cropSrc = URL.createObjectURL(arquivo);
            this.zoom = 1;
            this.cropAberto = true;
        },

        ajustarCrop() {
            const imagem = this.$refs.cropImagem;

            if (! imagem) {
                return;
            }

            this.imagemNaturalLargura = imagem.naturalWidth;
            this.imagemNaturalAltura = imagem.naturalHeight;
            this.baseScale = Math.max(
                this.viewport / this.imagemNaturalLargura,
                this.viewport / this.imagemNaturalAltura,
            );

            const escala = this.baseScale * this.zoom;
            this.offsetX = (this.viewport - this.imagemNaturalLargura * escala) / 2;
            this.offsetY = (this.viewport - this.imagemNaturalAltura * escala) / 2;
            this.limitarOffset();
        },

        limitarOffset() {
            const escala = this.baseScale * this.zoom;
            const largura = this.imagemNaturalLargura * escala;
            const altura = this.imagemNaturalAltura * escala;

            const minX = Math.min(0, this.viewport - largura);
            const minY = Math.min(0, this.viewport - altura);

            this.offsetX = Math.min(0, Math.max(minX, this.offsetX));
            this.offsetY = Math.min(0, Math.max(minY, this.offsetY));
        },

        iniciarArraste(event) {
            if (! this.cropAberto) {
                return;
            }

            this.arrastando = true;
            this.arrasteInicioX = event.clientX;
            this.arrasteInicioY = event.clientY;
            this.offsetInicioX = this.offsetX;
            this.offsetInicioY = this.offsetY;
            event.currentTarget.setPointerCapture?.(event.pointerId);
        },

        arrastar(event) {
            if (! this.arrastando) {
                return;
            }

            this.offsetX = this.offsetInicioX + (event.clientX - this.arrasteInicioX);
            this.offsetY = this.offsetInicioY + (event.clientY - this.arrasteInicioY);
            this.limitarOffset();
        },

        pararArraste() {
            this.arrastando = false;
        },

        cancelarCrop() {
            if (this.backupFotoFiles?.length && this.$refs.fotoInput) {
                const transferencia = new DataTransfer();

                [...this.backupFotoFiles].forEach((arquivo) => {
                    transferencia.items.add(arquivo);
                });

                this.$refs.fotoInput.files = transferencia.files;
            } else if (this.$refs.fotoInput) {
                this.$refs.fotoInput.value = '';
            }

            this.fecharCrop(false);
        },

        async aplicarCrop() {
            if (this.processandoCrop || ! this.imagemNaturalLargura) {
                return;
            }

            this.processandoCrop = true;

            try {
                const escala = this.baseScale * this.zoom;
                const origemX = Math.max(0, -this.offsetX / escala);
                const origemY = Math.max(0, -this.offsetY / escala);
                const tamanhoOrigem = this.viewport / escala;

                const canvas = document.createElement('canvas');
                canvas.width = this.saida;
                canvas.height = this.saida;

                const contexto = canvas.getContext('2d');
                contexto.drawImage(
                    this.$refs.cropImagem,
                    origemX,
                    origemY,
                    tamanhoOrigem,
                    tamanhoOrigem,
                    0,
                    0,
                    this.saida,
                    this.saida,
                );

                const blob = await new Promise((resolve, reject) => {
                    canvas.toBlob(
                        (resultado) => {
                            if (resultado) {
                                resolve(resultado);
                            } else {
                                reject(new Error('Falha ao gerar WebP.'));
                            }
                        },
                        'image/webp',
                        this.qualidadeWebp,
                    );
                });

                const arquivo = new File([blob], this.nomeArquivoCrop, {
                    type: 'image/webp',
                    lastModified: Date.now(),
                });

                const transferencia = new DataTransfer();
                transferencia.items.add(arquivo);
                this.$refs.fotoInput.files = transferencia.files;

                if (this.fotoUrl && this.fotoUrl.startsWith('blob:')) {
                    URL.revokeObjectURL(this.fotoUrl);
                }

                this.fotoUrl = URL.createObjectURL(arquivo);
                this.backupFotoFiles = this.$refs.fotoInput.files;
                this.fecharCrop(false);
            } catch (error) {
                console.error(error);
                this.processandoCrop = false;
            }
        },

        fecharCrop() {
            this.cropAberto = false;
            this.processandoCrop = false;
            this.arrastando = false;

            if (this.cropSrc && this.cropSrc.startsWith('blob:')) {
                URL.revokeObjectURL(this.cropSrc);
            }

            this.cropSrc = '';
        },
    });

    Alpine.data('categoriaFormulario', (estadoInicial = {}) => ({
        ...slugFormulario(estadoInicial),
        ...fotoCropFormulario({
            ...estadoInicial,
            nomeArquivoCrop: 'foto-categoria.webp',
        }),
        aba: estadoInicial.aba ?? 'principal',
        seoTitulo: estadoInicial.seoTitulo ?? '',
        seoDescricao: estadoInicial.seoDescricao ?? '',
    }));

    Alpine.data('menuItemFormulario', (estadoInicial = {}) => ({
        links: [...(estadoInicial.links ?? [])].map((link) => ({
            key: crypto.randomUUID(),
            id: link.id ?? null,
            rotulo: String(link.rotulo ?? ''),
            url: String(link.url ?? ''),
            target_blank: Boolean(link.target_blank),
        })),
        adicionarLink() {
            this.links.push({
                key: crypto.randomUUID(),
                id: null,
                rotulo: '',
                url: '',
                target_blank: false,
            });
        },
        removerLink(indice) {
            this.links.splice(indice, 1);
        },
    }));

    const criarArtigoFormulario = (estadoInicial = {}) => {
        // TipTap/ProseMirror não pode ficar no proxy reativo do Alpine.
        let editor = null;

        return {
        ...slugFormulario({
            ...estadoInicial,
            nome: estadoInicial.titulo ?? estadoInicial.nome ?? '',
        }),
        ...fotoCropFormulario({
            ...estadoInicial,
            nomeArquivoCrop: 'foto-artigo.webp',
        }),
        titulo: estadoInicial.titulo ?? '',
        aba: estadoInicial.aba ?? 'principal',
        seoTitulo: estadoInicial.seoTitulo ?? '',
        seoDescricao: estadoInicial.seoDescricao ?? '',
        autores: [...(estadoInicial.autores ?? [])].map((nome) => ({
            id: crypto.randomUUID(),
            nome: String(nome ?? ''),
        })),
        novoAutor: '',
        arrastandoAutorIndice: null,
        palavrasChave: [...(estadoInicial.palavrasChave ?? [])].map((nome) => String(nome ?? '')),
        opcoesPalavrasChave: [...(estadoInicial.opcoesPalavrasChave ?? [])].map((nome) => String(nome ?? '')),
        buscaPalavraChave: '',
        mostrarSugestoesPalavrasChave: false,
        indiceSugestaoPalavraChave: 0,
        areasTematicas: [...(estadoInicial.areasTematicas ?? [])].map((nome) => String(nome ?? '')),
        opcoesAreasTematicas: [...(estadoInicial.opcoesAreasTematicas ?? [])].map((nome) => String(nome ?? '')),
        buscaAreaTematica: '',
        mostrarSugestoesAreasTematicas: false,
        indiceSugestaoAreaTematica: 0,
        etiquetas: [...(estadoInicial.etiquetas ?? [])].map((nome) => String(nome ?? '')),
        opcoesEtiquetas: [...(estadoInicial.opcoesEtiquetas ?? [])].map((opcao) => ({
            nome: String(opcao?.nome ?? opcao ?? ''),
            cor: String(opcao?.cor ?? '#6366F1').toUpperCase(),
        })).filter((opcao) => opcao.nome !== ''),
        buscaEtiqueta: '',
        mostrarSugestoesEtiquetas: false,
        indiceSugestaoEtiqueta: 0,
        referencias: [...(estadoInicial.referencias ?? [])].map((texto) => ({
            id: crypto.randomUUID(),
            texto: String(texto ?? ''),
        })),
        novaReferencia: '',
        arrastandoReferenciaIndice: null,
        documentos: [...(estadoInicial.documentos ?? [])].map((documento) => ({
            clientId: crypto.randomUUID(),
            id: documento.id ?? null,
            titulo: String(documento.titulo ?? ''),
            tipo: documento.tipo === 'url' || (documento.url && ! documento.arquivoNome) ? 'url' : 'arquivo',
            url: String(documento.url ?? ''),
            arquivoNome: String(documento.arquivoNome ?? ''),
            arquivoUrl: String(documento.arquivoUrl ?? ''),
            arquivoNovoNome: '',
        })),
        arrastandoDocumentoIndice: null,
        conteudoHtml: String(estadoInicial.conteudoHtml ?? ''),
        urlUploadConteudoImagem: String(estadoInicial.urlUploadConteudoImagem ?? ''),
        coresTexto: [
            '#111827', '#6B7280', '#FFFFFF', '#DC2626', '#EA580C',
            '#CA8A04', '#16A34A', '#0D9488', '#2563EB', '#7C3AED', '#DB2777',
        ],
        coresDestaque: ['#FEF08A', '#BBF7D0', '#BFDBFE', '#FBCFE8', '#E9D5FF', '#FCA5A5'],
        fontesTexto: [
            { valor: '', rotulo: 'Fonte padrão' },
            { valor: 'Inter, sans-serif', rotulo: 'Inter' },
            { valor: 'Georgia, serif', rotulo: 'Georgia' },
            { valor: 'Times New Roman, Times, serif', rotulo: 'Times New Roman' },
            { valor: 'Arial, Helvetica, sans-serif', rotulo: 'Arial' },
            { valor: 'Courier New, Courier, monospace', rotulo: 'Courier New' },
            { valor: 'Verdana, Geneva, sans-serif', rotulo: 'Verdana' },
        ],
        tamanhosFonte: ['12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px', '36px', '48px'],
        editorConteudoTick: 0,
        enviandoImagemConteudo: false,
        erroImagemConteudo: '',
        editorTelaCheia: false,
        painelCorTexto: false,
        painelDestaque: false,
        codigoFonteAberto: false,
        codigoFonteHtml: '',
        codigoFonteTemaEscuro: true,
        codigoFonteTamanho: 14,
        codigoFonteCopiado: false,
        modalImagemAberto: false,
        modalImagemAba: 'upload',
        modalImagemUrl: '',
        modalImagemAlt: '',
        modalImagemArrastando: false,
        editorArrastandoImagem: false,

        init() {
            if (this.documentos.length === 0) {
                this.adicionarDocumento();
            }

            const formulario = this.$el.closest('form');

            if (formulario) {
                formulario.addEventListener('submit', (event) => this.tratarEnvioFormulario(event));
            }

            this.$watch('aba', (aba) => {
                if (aba === 'conteudo') {
                    this.$nextTick(() => {
                        requestAnimationFrame(() => this.garantirEditorConteudo());
                    });
                }
            });

            if (this.aba === 'conteudo') {
                this.$nextTick(() => {
                    requestAnimationFrame(() => this.garantirEditorConteudo());
                });
            }
        },

        tratarEnvioFormulario(event) {
            const formulario = event.target;

            if (! (formulario instanceof HTMLFormElement) || formulario.checkValidity()) {
                return;
            }

            event.preventDefault();

            const invalido = formulario.querySelector(':invalid');

            if (! (invalido instanceof HTMLElement)) {
                return;
            }

            const painel = invalido.closest('[x-show]');
            const expressao = painel?.getAttribute('x-show') ?? '';
            const correspondencia = expressao.match(/aba\s*===\s*'([^']+)'/);

            if (correspondencia?.[1]) {
                this.aba = correspondencia[1];
            }

            this.$nextTick(() => {
                invalido.focus({ preventScroll: false });
                if (typeof invalido.reportValidity === 'function') {
                    invalido.reportValidity();
                }
            });
        },

        destroy() {
            editor?.destroy();
            editor = null;
        },

        garantirEditorConteudo() {
            if (editor || ! this.$refs.editorConteudo) {
                return;
            }

            editor = new Editor({
                element: this.$refs.editorConteudo,
                extensions: [
                    StarterKit.configure({
                        heading: {
                            levels: [1, 2, 3, 4],
                        },
                    }),
                    TextStyle,
                    FontSize,
                    FontFamily,
                    Color,
                    Underline,
                    Subscript,
                    Superscript,
                    Highlight.configure({
                        multicolor: true,
                    }),
                    TextAlign.configure({
                        types: ['heading', 'paragraph'],
                    }),
                    Link.configure({
                        openOnClick: false,
                        autolink: true,
                        linkOnPaste: true,
                        HTMLAttributes: {
                            class: 'text-indigo-600 underline',
                            rel: 'noopener noreferrer',
                            target: '_blank',
                        },
                    }),
                    RichImage.configure({
                        allowBase64: false,
                        inline: false,
                        HTMLAttributes: {
                            class: 'editor-image',
                        },
                        resize: {
                            enabled: true,
                            directions: ['top-left', 'top-right', 'bottom-left', 'bottom-right'],
                            minWidth: 80,
                            minHeight: 80,
                            alwaysPreserveAspectRatio: true,
                        },
                    }),
                    Table.configure({
                        resizable: true,
                    }),
                    TableRow,
                    TableHeader,
                    TableCell,
                    TaskList,
                    TaskItem.configure({
                        nested: true,
                    }),
                    Placeholder.configure({
                        placeholder: 'Escreva o conteúdo do artigo…',
                    }),
                ],
                content: this.conteudoHtml || '',
                editorProps: {
                    attributes: {
                        class: 'focus:outline-none min-h-[24rem]',
                    },
                    handleDrop: (_view, event, _slice, moved) => {
                        if (moved) {
                            return false;
                        }

                        const arquivo = this.primeiroArquivoImagem(event.dataTransfer?.files);

                        if (! arquivo) {
                            return false;
                        }

                        event.preventDefault();
                        this.editorArrastandoImagem = false;
                        this.processarArquivoImagem(arquivo);

                        return true;
                    },
                    handlePaste: (_view, event) => {
                        const itens = Array.from(event.clipboardData?.items ?? []);
                        const itemImagem = itens.find((item) => item.type.startsWith('image/'));

                        if (! itemImagem) {
                            return false;
                        }

                        const arquivo = itemImagem.getAsFile();

                        if (! arquivo) {
                            return false;
                        }

                        event.preventDefault();
                        this.processarArquivoImagem(arquivo);

                        return true;
                    },
                },
                onUpdate: ({ editor: instancia }) => {
                    this.conteudoHtml = instancia.getHTML();
                    this.editorConteudoTick += 1;
                },
                onSelectionUpdate: () => {
                    this.editorConteudoTick += 1;
                },
                onTransaction: () => {
                    this.editorConteudoTick += 1;
                },
            });

            this.editorConteudoTick += 1;
        },

        comandoEditor(acao, atributos = {}) {
            if (! editor) {
                this.garantirEditorConteudo();
            }

            if (! editor) {
                return;
            }

            const cadeia = editor.chain().focus();

            switch (acao) {
                case 'bold':
                    cadeia.toggleBold().run();
                    break;
                case 'italic':
                    cadeia.toggleItalic().run();
                    break;
                case 'underline':
                    cadeia.toggleUnderline().run();
                    break;
                case 'strike':
                    cadeia.toggleStrike().run();
                    break;
                case 'highlight':
                    if (atributos.color) {
                        cadeia.toggleHighlight({ color: atributos.color }).run();
                    } else {
                        cadeia.toggleHighlight().run();
                    }
                    break;
                case 'unsetHighlight':
                    cadeia.unsetHighlight().run();
                    break;
                case 'color':
                    cadeia.setColor(atributos.color).run();
                    break;
                case 'unsetColor':
                    cadeia.unsetColor().run();
                    break;
                case 'code':
                    cadeia.toggleCode().run();
                    break;
                case 'codeBlock':
                    cadeia.toggleCodeBlock().run();
                    break;
                case 'heading':
                    cadeia.toggleHeading(atributos).run();
                    break;
                case 'bulletList':
                    cadeia.toggleBulletList().run();
                    break;
                case 'orderedList':
                    cadeia.toggleOrderedList().run();
                    break;
                case 'taskList':
                    cadeia.toggleTaskList().run();
                    break;
                case 'blockquote':
                    cadeia.toggleBlockquote().run();
                    break;
                case 'paragraph':
                    cadeia.clearNodes().setParagraph().run();
                    break;
                case 'horizontalRule':
                    cadeia.setHorizontalRule().run();
                    break;
                case 'align':
                    if (editor.isActive('image')) {
                        this.alinharImagem(atributos.align);
                    } else {
                        cadeia.setTextAlign(atributos.align).run();
                    }
                    break;
                case 'subscript':
                    cadeia.toggleSubscript().run();
                    break;
                case 'superscript':
                    cadeia.toggleSuperscript().run();
                    break;
                case 'citacao': {
                    const entrada = window.prompt(
                        'Números das referências (ex.: 30 ou 30, 31)',
                        atributos.refs ?? '',
                    );

                    if (entrada === null) {
                        break;
                    }

                    const numeros = String(entrada)
                        .split(/[^\d]+/)
                        .map((valor) => Number.parseInt(valor, 10))
                        .filter((valor) => Number.isFinite(valor) && valor > 0);

                    if (numeros.length === 0) {
                        break;
                    }

                    const unicos = [...new Set(numeros)];
                    const rotulo = unicos.join(', ');

                    cadeia
                        .insertContent(`<sup>${rotulo}</sup>`)
                        .run();
                    break;
                }
                case 'fontFamily':
                    if (! atributos.fontFamily) {
                        cadeia.unsetFontFamily().run();
                    } else {
                        cadeia.setFontFamily(atributos.fontFamily).run();
                    }
                    break;
                case 'fontSize':
                    if (! atributos.fontSize) {
                        cadeia.unsetFontSize().run();
                    } else {
                        cadeia.setFontSize(atributos.fontSize).run();
                    }
                    break;
                case 'indent':
                    if (editor.can().sinkListItem('listItem')) {
                        cadeia.sinkListItem('listItem').run();
                    } else if (editor.can().sinkListItem('taskItem')) {
                        cadeia.sinkListItem('taskItem').run();
                    }
                    break;
                case 'outdent':
                    if (editor.can().liftListItem('listItem')) {
                        cadeia.liftListItem('listItem').run();
                    } else if (editor.can().liftListItem('taskItem')) {
                        cadeia.liftListItem('taskItem').run();
                    }
                    break;
                case 'insertTable':
                    cadeia.insertTable({
                        rows: atributos.rows ?? 3,
                        cols: atributos.cols ?? 3,
                        withHeaderRow: true,
                    }).run();
                    break;
                case 'addColumnBefore':
                    cadeia.addColumnBefore().run();
                    break;
                case 'addColumnAfter':
                    cadeia.addColumnAfter().run();
                    break;
                case 'deleteColumn':
                    cadeia.deleteColumn().run();
                    break;
                case 'addRowBefore':
                    cadeia.addRowBefore().run();
                    break;
                case 'addRowAfter':
                    cadeia.addRowAfter().run();
                    break;
                case 'deleteRow':
                    cadeia.deleteRow().run();
                    break;
                case 'deleteTable':
                    cadeia.deleteTable().run();
                    break;
                case 'mergeCells':
                    cadeia.mergeCells().run();
                    break;
                case 'splitCell':
                    cadeia.splitCell().run();
                    break;
                case 'clear':
                    cadeia.clearNodes().unsetAllMarks().run();
                    break;
                case 'undo':
                    cadeia.undo().run();
                    break;
                case 'redo':
                    cadeia.redo().run();
                    break;
                default:
                    break;
            }
        },

        definirBloco(valor) {
            switch (valor) {
                case 'paragraph':
                    this.comandoEditor('paragraph');
                    break;
                case 'heading:1':
                    this.comandoEditor('heading', { level: 1 });
                    break;
                case 'heading:2':
                    this.comandoEditor('heading', { level: 2 });
                    break;
                case 'heading:3':
                    this.comandoEditor('heading', { level: 3 });
                    break;
                case 'heading:4':
                    this.comandoEditor('heading', { level: 4 });
                    break;
                case 'blockquote':
                    this.comandoEditor('blockquote');
                    break;
                case 'codeBlock':
                    this.comandoEditor('codeBlock');
                    break;
                default:
                    break;
            }
        },

        blocoAtual() {
            this.editorConteudoTick;

            if (! editor) {
                return 'paragraph';
            }

            if (editor.isActive('heading', { level: 1 })) {
                return 'heading:1';
            }

            if (editor.isActive('heading', { level: 2 })) {
                return 'heading:2';
            }

            if (editor.isActive('heading', { level: 3 })) {
                return 'heading:3';
            }

            if (editor.isActive('heading', { level: 4 })) {
                return 'heading:4';
            }

            if (editor.isActive('blockquote')) {
                return 'blockquote';
            }

            if (editor.isActive('codeBlock')) {
                return 'codeBlock';
            }

            return 'paragraph';
        },

        fonteAtual() {
            this.editorConteudoTick;

            if (! editor) {
                return '';
            }

            return editor.getAttributes('textStyle').fontFamily || '';
        },

        tamanhoFonteAtual() {
            this.editorConteudoTick;

            if (! editor) {
                return '16px';
            }

            return editor.getAttributes('textStyle').fontSize || '16px';
        },

        alternarTelaCheia() {
            this.editorTelaCheia = ! this.editorTelaCheia;

            if (this.editorTelaCheia) {
                this.$nextTick(() => this.garantirEditorConteudo());
            }
        },

        abrirCodigoFonte() {
            this.garantirEditorConteudo();
            this.codigoFonteHtml = formatarHtml(editor?.getHTML() ?? this.conteudoHtml);
            this.codigoFonteAberto = true;
            this.codigoFonteCopiado = false;
            this.$nextTick(() => this.$refs.codigoFonteTextarea?.focus());
        },

        formatarCodigoFonte() {
            this.codigoFonteHtml = formatarHtml(this.codigoFonteHtml);
        },

        async copiarCodigoFonte() {
            try {
                await navigator.clipboard.writeText(this.codigoFonteHtml);
                this.codigoFonteCopiado = true;
                setTimeout(() => {
                    this.codigoFonteCopiado = false;
                }, 1600);
            } catch (erro) {
                console.error(erro);
            }
        },

        salvarCodigoFonte() {
            this.garantirEditorConteudo();

            if (! editor) {
                return;
            }

            editor.commands.setContent(this.codigoFonteHtml || '', { emitUpdate: true });
            this.conteudoHtml = editor.getHTML();
            this.codigoFonteAberto = false;
            this.editorConteudoTick += 1;
        },

        cancelarCodigoFonte() {
            this.codigoFonteAberto = false;
            this.codigoFonteCopiado = false;
        },

        ajustarTamanhoCodigoFonte(delta) {
            this.codigoFonteTamanho = Math.min(28, Math.max(10, this.codigoFonteTamanho + delta));
        },

        editorEstaAtivo(nome, atributos = {}) {
            this.editorConteudoTick;

            if (! editor) {
                return false;
            }

            if (nome === 'textAlign') {
                if (editor.isActive('image')) {
                    return (editor.getAttributes('image').align || 'center') === atributos.align;
                }

                return editor.isActive({ textAlign: atributos.align });
            }

            if (nome === 'color') {
                return editor.isActive('textStyle', { color: atributos.color });
            }

            if (nome === 'highlight' && atributos.color) {
                return editor.isActive('highlight', { color: atributos.color });
            }

            return editor.isActive(nome, atributos);
        },

        corTextoAtual() {
            this.editorConteudoTick;

            if (! editor) {
                return '#111827';
            }

            return editor.getAttributes('textStyle').color || '#111827';
        },

        classeBotaoEditor(nome, atributos = {}) {
            const ativo = this.editorEstaAtivo(nome, atributos);

            return ativo
                ? 'rounded-md bg-indigo-100 px-2.5 py-1.5 text-sm font-medium text-indigo-700'
                : 'rounded-md px-2.5 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100';
        },

        adicionarLinkConteudo() {
            if (! editor) {
                this.garantirEditorConteudo();
            }

            if (! editor) {
                return;
            }

            const urlAtual = editor.getAttributes('link').href ?? '';
            const url = window.prompt('URL do link', urlAtual);

            if (url === null) {
                return;
            }

            if (url.trim() === '') {
                editor.chain().focus().extendMarkRange('link').unsetLink().run();

                return;
            }

            editor
                .chain()
                .focus()
                .extendMarkRange('link')
                .setLink({ href: url.trim() })
                .run();
        },

        primeiroArquivoImagem(lista) {
            const arquivos = Array.from(lista ?? []);

            return arquivos.find((arquivo) => arquivo.type.startsWith('image/')) ?? null;
        },

        abrirModalImagem() {
            if (! editor) {
                this.garantirEditorConteudo();
            }

            const attrs = this.atributosImagemAtual();
            const editando = Boolean(editor?.isActive('image'));

            this.modalImagemAba = 'upload';
            this.modalImagemUrl = editando ? String(attrs.src ?? '') : '';
            this.modalImagemAlt = editando ? String(attrs.alt ?? '') : '';
            this.modalImagemArrastando = false;
            this.erroImagemConteudo = '';
            this.modalImagemAberto = true;

            this.$nextTick(() => {
                this.$refs.modalImagemDropzone?.focus?.();
            });
        },

        fecharModalImagem(forcar = false) {
            if (this.enviandoImagemConteudo && ! forcar) {
                return;
            }

            this.modalImagemAberto = false;
            this.modalImagemArrastando = false;
            this.modalImagemUrl = '';
            this.modalImagemAlt = '';
            this.erroImagemConteudo = '';
        },

        selecionarImagemConteudo() {
            this.abrirModalImagem();
        },

        escolherArquivoImagem() {
            this.$refs.imagemConteudoInput?.click();
        },

        aoArrastarSobreEditor(event) {
            if (! Array.from(event.dataTransfer?.types ?? []).includes('Files')) {
                return;
            }

            event.preventDefault();
            this.editorArrastandoImagem = true;
        },

        aoSairArrastoEditor(event) {
            if (event?.currentTarget?.contains?.(event.relatedTarget)) {
                return;
            }

            this.editorArrastandoImagem = false;
        },

        aoSoltarNoEditor(event) {
            const arquivo = this.primeiroArquivoImagem(event.dataTransfer?.files);

            this.editorArrastandoImagem = false;

            if (! arquivo) {
                return;
            }

            event.preventDefault();
            this.processarArquivoImagem(arquivo);
        },

        aoArrastarSobreModal(event) {
            event.preventDefault();
            this.modalImagemArrastando = true;
        },

        aoSairArrastoModal(event) {
            if (event?.currentTarget?.contains?.(event.relatedTarget)) {
                return;
            }

            this.modalImagemArrastando = false;
        },

        aoSoltarNoModal(event) {
            event.preventDefault();
            this.modalImagemArrastando = false;

            const arquivo = this.primeiroArquivoImagem(event.dataTransfer?.files);

            if (arquivo) {
                this.processarArquivoImagem(arquivo);
            }
        },

        enviarImagemConteudo(event) {
            const arquivo = event.target.files?.[0] ?? null;

            event.target.value = '';

            if (arquivo) {
                this.processarArquivoImagem(arquivo);
            }
        },

        async processarArquivoImagem(arquivo) {
            if (! arquivo || ! this.urlUploadConteudoImagem) {
                return;
            }

            if (! arquivo.type.startsWith('image/')) {
                this.erroImagemConteudo = 'Selecione um arquivo de imagem (JPG, PNG, WebP ou GIF).';

                return;
            }

            if (! editor) {
                this.garantirEditorConteudo();
            }

            if (! editor) {
                return;
            }

            this.enviandoImagemConteudo = true;
            this.erroImagemConteudo = '';

            try {
                const dados = new FormData();
                dados.append('imagem', arquivo);

                const resposta = await fetch(this.urlUploadConteudoImagem, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: dados,
                });

                const corpo = await resposta.json().catch(() => ({}));

                if (! resposta.ok) {
                    const mensagem = corpo?.errors?.imagem?.[0]
                        ?? corpo?.message
                        ?? (resposta.status === 413
                            ? 'A imagem é muito grande para enviar.'
                            : 'Não foi possível enviar a imagem.');

                    throw new Error(mensagem);
                }

                if (! corpo.url) {
                    throw new Error('Resposta inválida do servidor.');
                }

                const altPadrao = this.modalImagemAlt.trim()
                    || arquivo.name.replace(/\.[^.]+$/, '');

                this.aplicarImagemNoEditor(corpo.url, altPadrao);
                this.enviandoImagemConteudo = false;
                this.fecharModalImagem(true);
            } catch (erro) {
                this.erroImagemConteudo = erro instanceof Error ? erro.message : 'Não foi possível enviar a imagem.';
            } finally {
                this.enviandoImagemConteudo = false;
            }
        },

        inserirImagemPorUrl() {
            const url = String(this.modalImagemUrl ?? '').trim();

            if (url === '') {
                this.erroImagemConteudo = 'Informe a URL da imagem.';

                return;
            }

            try {
                const validada = new URL(url);

                if (! ['http:', 'https:'].includes(validada.protocol)) {
                    throw new Error('URL inválida');
                }
            } catch {
                this.erroImagemConteudo = 'Informe uma URL válida começando com http:// ou https://.';

                return;
            }

            if (! editor) {
                this.garantirEditorConteudo();
            }

            if (! editor) {
                return;
            }

            const alt = this.modalImagemAlt.trim() || 'Imagem';

            this.aplicarImagemNoEditor(url, alt);
            this.fecharModalImagem();
        },

        aplicarImagemNoEditor(src, alt = '') {
            if (! editor) {
                return;
            }

            if (editor.isActive('image')) {
                editor
                    .chain()
                    .focus()
                    .updateAttributes('image', {
                        src,
                        alt: alt || editor.getAttributes('image').alt || '',
                    })
                    .run();

                return;
            }

            editor
                .chain()
                .focus()
                .setImage({
                    src,
                    alt,
                    align: 'center',
                })
                .run();
        },

        alinharImagem(align) {
            if (! editor || ! editor.isActive('image')) {
                return;
            }

            editor.chain().focus().updateAttributes('image', { align }).run();
            this.editorConteudoTick += 1;
        },

        removerImagemSelecionada() {
            if (! editor || ! editor.isActive('image')) {
                return;
            }

            editor.chain().focus().deleteSelection().run();
        },

        sincronizarSlug() {
            if (this.slugManual) {
                return;
            }

            this.slug = this.gerarSlug(this.titulo);
        },

        adicionarAutor() {
            const nome = String(this.novoAutor ?? '').trim();

            if (nome === '') {
                return;
            }

            this.autores.push({
                id: crypto.randomUUID(),
                nome,
            });
            this.novoAutor = '';
            this.$nextTick(() => this.$refs.novoAutorInput?.focus());
        },

        removerAutor(indice) {
            this.autores.splice(indice, 1);
        },

        iniciarArrasteAutor(event) {
            const item = event.currentTarget.closest('[data-indice]');
            this.arrastandoAutorIndice = Number(item?.dataset.indice);
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(this.arrastandoAutorIndice));
        },

        arrastarSobreAutor(event) {
            const alvo = event.currentTarget;
            const indiceAlvo = Number(alvo.dataset.indice);

            if (this.arrastandoAutorIndice === null || Number.isNaN(indiceAlvo) || indiceAlvo === this.arrastandoAutorIndice) {
                return;
            }

            const autores = [...this.autores];
            const [movido] = autores.splice(this.arrastandoAutorIndice, 1);
            autores.splice(indiceAlvo, 0, movido);
            this.autores = autores;
            this.arrastandoAutorIndice = indiceAlvo;
        },

        finalizarArrasteAutor() {
            this.arrastandoAutorIndice = null;
        },

        get sugestoesPalavrasChave() {
            const busca = String(this.buscaPalavraChave ?? '').trim();
            const selecionadas = new Set(this.palavrasChave.map((nome) => nome.toLowerCase()));
            const sugestoes = [];

            if (busca !== '') {
                const existeExata = this.opcoesPalavrasChave.some(
                    (nome) => nome.toLowerCase() === busca.toLowerCase(),
                );
                const jaSelecionada = selecionadas.has(busca.toLowerCase());

                if (! existeExata && ! jaSelecionada) {
                    sugestoes.push({
                        valor: busca,
                        rotulo: `Adicionar “${busca}”`,
                        criar: true,
                    });
                }
            }

            this.opcoesPalavrasChave
                .filter((nome) => ! selecionadas.has(nome.toLowerCase()))
                .filter((nome) => busca === '' || nome.toLowerCase().includes(busca.toLowerCase()))
                .slice(0, 12)
                .forEach((nome) => {
                    sugestoes.push({
                        valor: nome,
                        rotulo: nome,
                        criar: false,
                    });
                });

            return sugestoes;
        },

        abrirSugestoesPalavrasChave() {
            this.mostrarSugestoesPalavrasChave = true;
            this.indiceSugestaoPalavraChave = 0;
        },

        fecharSugestoesPalavrasChave() {
            this.mostrarSugestoesPalavrasChave = false;
            this.indiceSugestaoPalavraChave = 0;
        },

        navegarSugestaoPalavraChave(delta) {
            if (! this.mostrarSugestoesPalavrasChave || this.sugestoesPalavrasChave.length === 0) {
                this.abrirSugestoesPalavrasChave();

                return;
            }

            const total = this.sugestoesPalavrasChave.length;
            this.indiceSugestaoPalavraChave = (this.indiceSugestaoPalavraChave + delta + total) % total;
        },

        confirmarPalavraChave() {
            if (this.mostrarSugestoesPalavrasChave && this.sugestoesPalavrasChave.length > 0) {
                this.selecionarSugestaoPalavraChave(
                    this.sugestoesPalavrasChave[this.indiceSugestaoPalavraChave],
                );

                return;
            }

            const nome = String(this.buscaPalavraChave ?? '').trim();

            if (nome !== '') {
                this.adicionarPalavraChave(nome);
            }
        },

        selecionarSugestaoPalavraChave(sugestao) {
            if (! sugestao) {
                return;
            }

            this.adicionarPalavraChave(sugestao.valor);
        },

        adicionarPalavraChave(nome) {
            const valor = String(nome ?? '').trim();

            if (valor === '') {
                return;
            }

            const jaExiste = this.palavrasChave.some(
                (atual) => atual.toLowerCase() === valor.toLowerCase(),
            );

            if (! jaExiste) {
                this.palavrasChave.push(valor);

                if (! this.opcoesPalavrasChave.some((opcao) => opcao.toLowerCase() === valor.toLowerCase())) {
                    this.opcoesPalavrasChave.push(valor);
                    this.opcoesPalavrasChave.sort((a, b) => a.localeCompare(b, 'pt-BR'));
                }
            }

            this.buscaPalavraChave = '';
            this.indiceSugestaoPalavraChave = 0;
            this.mostrarSugestoesPalavrasChave = true;
            this.$nextTick(() => this.$refs.palavraChaveInput?.focus());
        },

        removerPalavraChave(indice) {
            this.palavrasChave.splice(indice, 1);
        },

        removerUltimaPalavraChaveSeVazio() {
            if (String(this.buscaPalavraChave ?? '') !== '' || this.palavrasChave.length === 0) {
                return;
            }

            this.palavrasChave.pop();
        },

        get sugestoesAreasTematicas() {
            const busca = String(this.buscaAreaTematica ?? '').trim();
            const selecionadas = new Set(this.areasTematicas.map((nome) => nome.toLowerCase()));
            const sugestoes = [];

            if (busca !== '') {
                const existeExata = this.opcoesAreasTematicas.some(
                    (nome) => nome.toLowerCase() === busca.toLowerCase(),
                );
                const jaSelecionada = selecionadas.has(busca.toLowerCase());

                if (! existeExata && ! jaSelecionada) {
                    sugestoes.push({
                        valor: busca,
                        rotulo: `Adicionar “${busca}”`,
                        criar: true,
                    });
                }
            }

            this.opcoesAreasTematicas
                .filter((nome) => ! selecionadas.has(nome.toLowerCase()))
                .filter((nome) => busca === '' || nome.toLowerCase().includes(busca.toLowerCase()))
                .slice(0, 12)
                .forEach((nome) => {
                    sugestoes.push({
                        valor: nome,
                        rotulo: nome,
                        criar: false,
                    });
                });

            return sugestoes;
        },

        abrirSugestoesAreasTematicas() {
            this.mostrarSugestoesAreasTematicas = true;
            this.indiceSugestaoAreaTematica = 0;
        },

        fecharSugestoesAreasTematicas() {
            this.mostrarSugestoesAreasTematicas = false;
            this.indiceSugestaoAreaTematica = 0;
        },

        navegarSugestaoAreaTematica(delta) {
            if (! this.mostrarSugestoesAreasTematicas || this.sugestoesAreasTematicas.length === 0) {
                this.abrirSugestoesAreasTematicas();

                return;
            }

            const total = this.sugestoesAreasTematicas.length;
            this.indiceSugestaoAreaTematica = (this.indiceSugestaoAreaTematica + delta + total) % total;
        },

        confirmarAreaTematica() {
            if (this.mostrarSugestoesAreasTematicas && this.sugestoesAreasTematicas.length > 0) {
                this.selecionarSugestaoAreaTematica(
                    this.sugestoesAreasTematicas[this.indiceSugestaoAreaTematica],
                );

                return;
            }

            const nome = String(this.buscaAreaTematica ?? '').trim();

            if (nome !== '') {
                this.adicionarAreaTematica(nome);
            }
        },

        selecionarSugestaoAreaTematica(sugestao) {
            if (! sugestao) {
                return;
            }

            this.adicionarAreaTematica(sugestao.valor);
        },

        adicionarAreaTematica(nome) {
            const valor = String(nome ?? '').trim();

            if (valor === '') {
                return;
            }

            const jaExiste = this.areasTematicas.some(
                (atual) => atual.toLowerCase() === valor.toLowerCase(),
            );

            if (! jaExiste) {
                this.areasTematicas.push(valor);

                if (! this.opcoesAreasTematicas.some((opcao) => opcao.toLowerCase() === valor.toLowerCase())) {
                    this.opcoesAreasTematicas.push(valor);
                    this.opcoesAreasTematicas.sort((a, b) => a.localeCompare(b, 'pt-BR'));
                }
            }

            this.buscaAreaTematica = '';
            this.indiceSugestaoAreaTematica = 0;
            this.mostrarSugestoesAreasTematicas = true;
            this.$nextTick(() => this.$refs.areaTematicaInput?.focus());
        },

        removerAreaTematica(indice) {
            this.areasTematicas.splice(indice, 1);
        },

        removerUltimaAreaTematicaSeVazio() {
            if (String(this.buscaAreaTematica ?? '') !== '' || this.areasTematicas.length === 0) {
                return;
            }

            this.areasTematicas.pop();
        },

        corEtiqueta(nome) {
            const chave = String(nome ?? '').toLowerCase();
            const opcao = this.opcoesEtiquetas.find((item) => item.nome.toLowerCase() === chave);

            return opcao?.cor || '#6366F1';
        },

        corTextoEtiqueta(cor) {
            const hex = String(cor ?? '#6366F1').replace('#', '');

            if (hex.length !== 6) {
                return '#ffffff';
            }

            const r = Number.parseInt(hex.slice(0, 2), 16);
            const g = Number.parseInt(hex.slice(2, 4), 16);
            const b = Number.parseInt(hex.slice(4, 6), 16);
            const luminancia = ((0.299 * r) + (0.587 * g) + (0.114 * b)) / 255;

            return luminancia > 0.6 ? '#111827' : '#ffffff';
        },

        estiloEtiqueta(nome) {
            const fundo = this.corEtiqueta(nome);

            return `background-color: ${fundo}; color: ${this.corTextoEtiqueta(fundo)}`;
        },

        get sugestoesEtiquetas() {
            const busca = String(this.buscaEtiqueta ?? '').trim();
            const selecionadas = new Set(this.etiquetas.map((nome) => nome.toLowerCase()));
            const sugestoes = [];

            if (busca !== '') {
                const existeExata = this.opcoesEtiquetas.some(
                    (opcao) => opcao.nome.toLowerCase() === busca.toLowerCase(),
                );
                const jaSelecionada = selecionadas.has(busca.toLowerCase());

                if (! existeExata && ! jaSelecionada) {
                    sugestoes.push({
                        valor: busca,
                        rotulo: `Adicionar “${busca}”`,
                        cor: '#6366F1',
                        criar: true,
                    });
                }
            }

            this.opcoesEtiquetas
                .filter((opcao) => ! selecionadas.has(opcao.nome.toLowerCase()))
                .filter((opcao) => busca === '' || opcao.nome.toLowerCase().includes(busca.toLowerCase()))
                .slice(0, 12)
                .forEach((opcao) => {
                    sugestoes.push({
                        valor: opcao.nome,
                        rotulo: opcao.nome,
                        cor: opcao.cor,
                        criar: false,
                    });
                });

            return sugestoes;
        },

        abrirSugestoesEtiquetas() {
            this.mostrarSugestoesEtiquetas = true;
            this.indiceSugestaoEtiqueta = 0;
        },

        fecharSugestoesEtiquetas() {
            this.mostrarSugestoesEtiquetas = false;
            this.indiceSugestaoEtiqueta = 0;
        },

        navegarSugestaoEtiqueta(delta) {
            if (! this.mostrarSugestoesEtiquetas || this.sugestoesEtiquetas.length === 0) {
                this.abrirSugestoesEtiquetas();

                return;
            }

            const total = this.sugestoesEtiquetas.length;
            this.indiceSugestaoEtiqueta = (this.indiceSugestaoEtiqueta + delta + total) % total;
        },

        confirmarEtiqueta() {
            if (this.mostrarSugestoesEtiquetas && this.sugestoesEtiquetas.length > 0) {
                this.selecionarSugestaoEtiqueta(
                    this.sugestoesEtiquetas[this.indiceSugestaoEtiqueta],
                );

                return;
            }

            const nome = String(this.buscaEtiqueta ?? '').trim();

            if (nome !== '') {
                this.adicionarEtiqueta(nome);
            }
        },

        selecionarSugestaoEtiqueta(sugestao) {
            if (! sugestao) {
                return;
            }

            this.adicionarEtiqueta(sugestao.valor, sugestao.cor);
        },

        adicionarEtiqueta(nome, cor = '#6366F1') {
            const valor = String(nome ?? '').trim();

            if (valor === '') {
                return;
            }

            const jaExiste = this.etiquetas.some(
                (atual) => atual.toLowerCase() === valor.toLowerCase(),
            );

            if (! jaExiste) {
                this.etiquetas.push(valor);

                if (! this.opcoesEtiquetas.some((opcao) => opcao.nome.toLowerCase() === valor.toLowerCase())) {
                    this.opcoesEtiquetas.push({
                        nome: valor,
                        cor: String(cor ?? '#6366F1').toUpperCase(),
                    });
                    this.opcoesEtiquetas.sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'));
                }
            }

            this.buscaEtiqueta = '';
            this.indiceSugestaoEtiqueta = 0;
            this.mostrarSugestoesEtiquetas = true;
            this.$nextTick(() => this.$refs.etiquetaInput?.focus());
        },

        removerEtiqueta(indice) {
            this.etiquetas.splice(indice, 1);
        },

        removerUltimaEtiquetaSeVazio() {
            if (String(this.buscaEtiqueta ?? '') !== '' || this.etiquetas.length === 0) {
                return;
            }

            this.etiquetas.pop();
        },

        adicionarReferencia() {
            const texto = String(this.novaReferencia ?? '').trim();

            if (texto === '') {
                return;
            }

            this.referencias.push({
                id: crypto.randomUUID(),
                texto,
            });
            this.novaReferencia = '';
            this.$nextTick(() => this.$refs.novaReferenciaInput?.focus());
        },

        removerReferencia(indice) {
            this.referencias.splice(indice, 1);
        },

        iniciarArrasteReferencia(event) {
            const item = event.currentTarget.closest('[data-indice]');
            this.arrastandoReferenciaIndice = Number(item?.dataset.indice);
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(this.arrastandoReferenciaIndice));
        },

        arrastarSobreReferencia(event) {
            const alvo = event.currentTarget;
            const indiceAlvo = Number(alvo.dataset.indice);

            if (
                this.arrastandoReferenciaIndice === null
                || Number.isNaN(indiceAlvo)
                || indiceAlvo === this.arrastandoReferenciaIndice
            ) {
                return;
            }

            const referencias = [...this.referencias];
            const [movida] = referencias.splice(this.arrastandoReferenciaIndice, 1);
            referencias.splice(indiceAlvo, 0, movida);
            this.referencias = referencias;
            this.arrastandoReferenciaIndice = indiceAlvo;
        },

        finalizarArrasteReferencia() {
            this.arrastandoReferenciaIndice = null;
        },

        adicionarDocumento() {
            this.documentos.push({
                clientId: crypto.randomUUID(),
                id: null,
                titulo: '',
                tipo: 'arquivo',
                url: '',
                arquivoNome: '',
                arquivoUrl: '',
                arquivoNovoNome: '',
            });
        },

        removerDocumento(indice) {
            this.documentos.splice(indice, 1);

            if (this.documentos.length === 0) {
                this.adicionarDocumento();
            }
        },

        definirTipoDocumento(indice, tipo) {
            if (! this.documentos[indice]) {
                return;
            }

            this.documentos[indice].tipo = tipo;

            if (tipo === 'arquivo') {
                this.documentos[indice].url = '';
            } else {
                this.documentos[indice].arquivoNovoNome = '';
            }
        },

        atualizarNomeArquivoDocumento(indice, event) {
            const arquivo = event.target.files?.[0];

            if (! this.documentos[indice]) {
                return;
            }

            this.documentos[indice].arquivoNovoNome = arquivo ? arquivo.name : '';
        },

        iniciarArrasteDocumento(event) {
            const item = event.currentTarget.closest('[data-indice]');
            this.arrastandoDocumentoIndice = Number(item?.dataset.indice);
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(this.arrastandoDocumentoIndice));
        },

        arrastarSobreDocumento(event) {
            const alvo = event.currentTarget;
            const indiceAlvo = Number(alvo.dataset.indice);

            if (
                this.arrastandoDocumentoIndice === null
                || Number.isNaN(indiceAlvo)
                || indiceAlvo === this.arrastandoDocumentoIndice
            ) {
                return;
            }

            const documentos = [...this.documentos];
            const [movido] = documentos.splice(this.arrastandoDocumentoIndice, 1);
            documentos.splice(indiceAlvo, 0, movido);
            this.documentos = documentos;
            this.arrastandoDocumentoIndice = indiceAlvo;
        },

        finalizarArrasteDocumento() {
            this.arrastandoDocumentoIndice = null;
        },
        };
    };

    Alpine.data('artigoFormulario', criarArtigoFormulario);

    Alpine.data('paginaFormulario', (estadoInicial = {}) => {
        const form = criarArtigoFormulario({
            titulo: estadoInicial.titulo ?? '',
            nome: estadoInicial.titulo ?? estadoInicial.nome ?? '',
            slug: estadoInicial.slug ?? '',
            slugManual: Boolean(estadoInicial.slugManual),
            aba: estadoInicial.aba ?? 'principal',
            seoTitulo: estadoInicial.seoTitulo ?? '',
            seoDescricao: estadoInicial.seoDescricao ?? '',
            conteudoHtml: estadoInicial.conteudoHtml ?? '',
            urlUploadConteudoImagem: estadoInicial.urlUploadConteudoImagem ?? '',
            autores: [],
            palavrasChave: [],
            opcoesPalavrasChave: [],
            areasTematicas: [],
            opcoesAreasTematicas: [],
            etiquetas: [],
            opcoesEtiquetas: [],
            referencias: [],
            documentos: [],
        });

        const initArtigo = form.init;

        return {
            ...form,
            ativo: Boolean(estadoInicial.ativo ?? true),
            init() {
                initArtigo.call(this);

                this.$watch('aba', (aba) => {
                    if (aba === 'principal') {
                        this.$nextTick(() => {
                            requestAnimationFrame(() => this.garantirEditorConteudo());
                        });
                    }
                });

                if (this.aba === 'principal') {
                    this.$nextTick(() => {
                        requestAnimationFrame(() => this.garantirEditorConteudo());
                    });
                }
            },
            sincronizarSlugDoTitulo() {
                this.nome = this.titulo;
                this.sincronizarSlug();
            },
        };
    });

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

    Alpine.data('artigoReferenciasPainel', (referencias = []) => ({
        aberto: false,
        ativos: [],
        referencias: Array.isArray(referencias) ? referencias : [],

        tratarCliqueCitacao(event) {
            const alvo = event.target.closest('.citacao-ref');

            if (! alvo) {
                return;
            }

            event.preventDefault();
            this.abrir(alvo.getAttribute('data-refs') || '');
            document.querySelectorAll('.citacao-ref.is-active').forEach((el) => {
                el.classList.remove('is-active');
            });
            alvo.classList.add('is-active');
        },

        abrir(refsCsv) {
            const numeros = String(refsCsv)
                .split(',')
                .map((valor) => Number.parseInt(valor.trim(), 10))
                .filter((valor) => Number.isFinite(valor) && valor > 0);

            this.ativos = [...new Set(numeros)];
            this.aberto = true;

            this.$nextTick(() => {
                const primeiro = this.ativos[0];
                if (! primeiro) {
                    return;
                }

                const item = this.$refs.listaPainel?.querySelector(`#painel-ref-${primeiro}`);
                item?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
            });
        },

        fechar() {
            this.aberto = false;
            this.ativos = [];
            document.querySelectorAll('.citacao-ref.is-active').forEach((el) => {
                el.classList.remove('is-active');
            });
        },
    }));
});

Alpine.start();
