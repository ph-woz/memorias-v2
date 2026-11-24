export function formatarHtml(html) {
    const bruto = String(html ?? '').trim();

    if (bruto === '') {
        return '';
    }

    const vazios = /^(area|base|br|col|embed|hr|img|input|link|meta|param|source|track|wbr)$/i;
    let nivel = 0;

    return bruto
        .replace(/>\s*</g, '>\n<')
        .split('\n')
        .map((linha) => linha.trim())
        .filter((linha) => linha !== '')
        .map((linha) => {
            const fechando = /^<\/\w/.test(linha);

            if (fechando) {
                nivel = Math.max(nivel - 1, 0);
            }

            const formatada = `${'  '.repeat(nivel)}${linha}`;
            const abertura = linha.match(/^<([a-zA-Z][\w:-]*)\b[^>]*>$/);

            if (
                abertura
                && ! linha.endsWith('/>')
                && ! vazios.test(abertura[1])
                && ! fechando
            ) {
                nivel += 1;
            }

            return formatada;
        })
        .join('\n');
}
