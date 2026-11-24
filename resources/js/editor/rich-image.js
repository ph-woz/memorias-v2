import Image from '@tiptap/extension-image';
import { ResizableNodeView, getRenderedAttributes, mergeAttributes } from '@tiptap/core';

function aplicarAlinhamento(container, align = 'center') {
    if (! container) {
        return;
    }

    const valor = align || 'center';

    container.dataset.align = valor;
    container.style.display = 'flex';
    container.style.width = '100%';
    container.style.justifyContent = valor === 'left'
        ? 'flex-start'
        : valor === 'right'
            ? 'flex-end'
            : 'center';
}

/**
 * Imagem com resize nativo (handles) e alinhamento via data-align.
 * Sem float/rotate — mantém o código simples e o resize confiável.
 */
export const RichImage = Image.extend({
    name: 'image',
    draggable: true,

    addAttributes() {
        return {
            ...this.parent?.(),
            align: {
                default: 'center',
                parseHTML: (element) => {
                    const host = element.closest?.('[data-align]') ?? element;

                    return host.getAttribute('data-align') || 'center';
                },
                renderHTML: (attributes) => ({
                    'data-align': attributes.align || 'center',
                }),
            },
        };
    },

    renderHTML({ HTMLAttributes }) {
        return ['img', mergeAttributes(this.options.HTMLAttributes, HTMLAttributes)];
    },

    addNodeView() {
        if (! this.options.resize || ! this.options.resize.enabled || typeof document === 'undefined') {
            return null;
        }

        const { directions, minWidth, minHeight, alwaysPreserveAspectRatio } = this.options.resize;
        const atributosGerenciadosPeloResize = new Set(['src', 'width', 'height']);

        return ({ node, getPos, HTMLAttributes, editor }) => {
            const el = document.createElement('img');
            el.draggable = false;

            const mergedAttributes = mergeAttributes(this.options.HTMLAttributes, HTMLAttributes);

            Object.entries(mergedAttributes).forEach(([key, value]) => {
                if (value == null) {
                    return;
                }

                if (key === 'src' || key === 'width' || key === 'height') {
                    return;
                }

                el.setAttribute(key, value);
            });

            if (mergedAttributes.src) {
                el.src = mergedAttributes.src;
            }

            let previousHTMLAttributes = { ...HTMLAttributes };

            const syncImageSource = (src) => {
                if (typeof src === 'string' && src !== '') {
                    if (el.getAttribute('src') !== src) {
                        el.src = src;
                    }

                    return;
                }

                if (el.hasAttribute('src')) {
                    el.removeAttribute('src');
                }
            };

            syncImageSource(HTMLAttributes.src);

            const onUpdate = (updatedNode) => {
                if (updatedNode.type !== node.type) {
                    return false;
                }

                const extensionAttributes = editor.extensionManager.attributes.filter(
                    (attribute) => attribute.type === updatedNode.type.name,
                );
                const newHTMLAttributes = getRenderedAttributes(updatedNode, extensionAttributes);

                Object.keys(previousHTMLAttributes).forEach((key) => {
                    if (! atributosGerenciadosPeloResize.has(key) && ! (key in newHTMLAttributes)) {
                        el.removeAttribute(key);
                    }
                });

                Object.entries(newHTMLAttributes).forEach(([key, value]) => {
                    if (atributosGerenciadosPeloResize.has(key)) {
                        return;
                    }

                    if (value != null) {
                        el.setAttribute(key, value);
                    } else {
                        el.removeAttribute(key);
                    }
                });

                syncImageSource(newHTMLAttributes.src);
                previousHTMLAttributes = newHTMLAttributes;
                aplicarAlinhamento(nodeView.dom, updatedNode.attrs.align);

                return true;
            };

            const nodeView = new ResizableNodeView({
                element: el,
                editor,
                node,
                getPos,
                onResize: (width, height) => {
                    el.style.width = `${width}px`;
                    el.style.height = `${height}px`;
                },
                onCommit: (width, height) => {
                    const pos = getPos();

                    if (pos === undefined) {
                        return;
                    }

                    this.editor
                        .chain()
                        .setNodeSelection(pos)
                        .updateAttributes(this.name, { width, height })
                        .run();
                },
                onUpdate,
                options: {
                    directions,
                    min: {
                        width: minWidth,
                        height: minHeight,
                    },
                    preserveAspectRatio: alwaysPreserveAspectRatio === true,
                    className: {
                        container: 'editor-image-container',
                        wrapper: 'editor-image-wrapper',
                        handle: 'editor-image-handle',
                        resizing: 'is-resizing',
                    },
                },
            });

            aplicarAlinhamento(nodeView.dom, node.attrs.align);

            const showNodeView = () => {
                nodeView.dom.style.visibility = '';
                nodeView.dom.style.pointerEvents = '';
            };

            nodeView.dom.style.visibility = 'hidden';
            nodeView.dom.style.pointerEvents = 'none';

            if (el.complete && el.naturalWidth > 0) {
                showNodeView();
            } else {
                el.onload = showNodeView;
                el.onerror = showNodeView;
            }

            return nodeView;
        };
    },
});
