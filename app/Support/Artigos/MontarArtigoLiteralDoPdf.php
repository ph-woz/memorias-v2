<?php

namespace App\Support\Artigos;

use RuntimeException;

class MontarArtigoLiteralDoPdf
{
    public function __construct(
        private readonly ValidarFidelidadeExtracaoPdf $validador = new ValidarFidelidadeExtracaoPdf,
    ) {}

    /**
     * @param  array<string, mixed>  $metadados
     * @return array<string, mixed>
     */
    public function montar(string $textoPdf, array $metadados): array
    {
        $textoPdf = trim($textoPdf);

        if (mb_strlen($textoPdf) < 500) {
            throw new RuntimeException(
                'O PDF não possui texto extraível suficiente para importação literal. Use um PDF com texto selecionável (não apenas imagem).'
            );
        }

        $metadados = $this->ancorarCamposLiterais($textoPdf, $metadados);
        $metadados = $this->descartarItensNaoLiterais($textoPdf, $metadados);
        $this->validador->validar($textoPdf, $metadados);

        $inicioCorpo = trim((string) ($metadados['marcador_inicio_corpo'] ?? ''));
        $inicioRefs = trim((string) ($metadados['marcador_inicio_referencias'] ?? ''));

        $posCorpo = $inicioCorpo !== '' ? $this->encontrarPosicao($textoPdf, $inicioCorpo) : null;
        $posRefs = $inicioRefs !== '' ? $this->encontrarPosicao($textoPdf, $inicioRefs) : null;

        // Portal/PDF Memórias: corpo começa após Key words (layout atual) ou após Accepted
        // (layout clássico). A IA costuma apontar para o meio do artigo — sobrescrevemos.
        $posCorpo = $this->resolverInicioCorpoEditorial($textoPdf, $posCorpo, $metadados);

        if ($posCorpo === null) {
            throw new RuntimeException('Não foi possível localizar o início do corpo do artigo no texto do PDF.');
        }

        if ($posRefs !== null && $posRefs <= $posCorpo) {
            $posRefs = null;
        }

        if ($posRefs === null) {
            $posRefs = $this->inferirInicioReferencias($textoPdf, $posCorpo);
        }

        $trechoCorpo = $posRefs !== null
            ? mb_substr($textoPdf, $posCorpo, $posRefs - $posCorpo)
            : mb_substr($textoPdf, $posCorpo);

        $trechoRefs = $posRefs !== null
            ? mb_substr($textoPdf, $posRefs)
            : '';

        $trechoCorpo = $this->removerCabecalhosDePagina(trim($trechoCorpo));
        $trechoCorpo = $this->fundirFragmentoDeColuna($textoPdf, $trechoCorpo);
        $trechoCorpo = $this->removerBlocosLateraisIntercalados($trechoCorpo);
        $trechoCorpo = $this->normalizarCitacoesDoPdf($trechoCorpo);
        $metadados['conteudo_html'] = $this->textoParaHtmlLiteral($trechoCorpo);
        $metadados['referencias'] = $this->referenciasLiterais(trim($trechoRefs));

        if (trim(strip_tags($metadados['conteudo_html'])) === '') {
            throw new RuntimeException('O conteúdo literal do artigo ficou vazio após o fatiamento do PDF.');
        }

        return $metadados;
    }

    /**
     * Substitui campos da IA pelos trechos literais encontrados no PDF
     * (corrige pequenas divergências de espaços/hifenização da extração).
     *
     * @param  array<string, mixed>  $metadados
     * @return array<string, mixed>
     */
    private function ancorarCamposLiterais(string $textoPdf, array $metadados): array
    {
        foreach (['titulo', 'resumo'] as $campo) {
            $valor = trim((string) ($metadados[$campo] ?? ''));

            if ($valor === '') {
                continue;
            }

            $ancora = $this->ancorarTrecho($textoPdf, $valor);

            if ($ancora !== null) {
                $metadados[$campo] = $ancora;
            }
        }

        // Campos compostos (várias linhas): só ancora se o bloco inteiro existir;
        // encurtamento parcial perderia e-mail/ORCID/afiliações.
        foreach (['endereco_instituicoes', 'autor_correspondente'] as $campo) {
            $valor = trim((string) ($metadados[$campo] ?? ''));

            if ($valor === '') {
                continue;
            }

            $literal = $this->validador->localizarTrechoLiteral($textoPdf, $valor);

            if ($literal !== null) {
                $metadados[$campo] = $this->compactarEspacosPreservandoTexto($literal);
            }
        }

        foreach (['autores', 'palavras_chave', 'areas_tematicas'] as $lista) {
            $itens = $metadados[$lista] ?? [];

            if (! is_array($itens)) {
                continue;
            }

            $ancorados = [];

            foreach ($itens as $item) {
                $valor = trim((string) $item);

                if ($valor === '') {
                    continue;
                }

                $ancora = $this->ancorarTrecho($textoPdf, $valor);
                $ancorados[] = $ancora ?? $valor;
            }

            $metadados[$lista] = $ancorados;
        }

        $marcadorCorpo = trim((string) ($metadados['marcador_inicio_corpo'] ?? ''));
        $ancoraCorpo = $marcadorCorpo !== '' ? $this->ancorarTrecho($textoPdf, $marcadorCorpo) : null;

        if ($ancoraCorpo === null) {
            $pos = $this->inferirInicioCorpo($textoPdf, $metadados);

            if ($pos !== null) {
                $ancoraCorpo = trim(mb_substr($textoPdf, $pos, 80));
            }
        }

        $metadados['marcador_inicio_corpo'] = $ancoraCorpo ?? '';

        $marcadorRefs = trim((string) ($metadados['marcador_inicio_referencias'] ?? ''));
        $ancoraRefs = $marcadorRefs !== '' ? $this->ancorarTrecho($textoPdf, $marcadorRefs) : null;

        if ($ancoraRefs === null) {
            $posCorpo = $ancoraCorpo !== null && $ancoraCorpo !== ''
                ? $this->encontrarPosicao($textoPdf, $ancoraCorpo)
                : 0;
            $posRefs = $this->inferirInicioReferencias($textoPdf, $posCorpo ?? 0);

            if ($posRefs !== null) {
                $ancoraRefs = trim(mb_substr($textoPdf, $posRefs, 40));
            }
        }

        $metadados['marcador_inicio_referencias'] = $ancoraRefs ?? '';

        return $metadados;
    }

    private function ancorarTrecho(string $textoPdf, string $trecho): ?string
    {
        $trecho = trim($trecho);

        if ($trecho === '') {
            return null;
        }

        $literal = $this->validador->localizarTrechoLiteral($textoPdf, $trecho);

        if ($literal !== null) {
            return $this->compactarEspacosPreservandoTexto($literal);
        }

        // Encurta progressivamente o marcador até achar um trecho literal.
        $candidato = $trecho;

        while (mb_strlen($candidato) >= 24) {
            $candidato = trim(mb_substr($candidato, 0, (int) floor(mb_strlen($candidato) * 0.75)));

            if ($candidato === '') {
                break;
            }

            $literal = $this->validador->localizarTrechoLiteral($textoPdf, $candidato);

            if ($literal !== null) {
                return $this->compactarEspacosPreservandoTexto($literal);
            }
        }

        // Última tentativa: primeiras 8–12 palavras.
        $palavras = preg_split('/\s+/u', $trecho) ?: [];

        for ($n = min(12, count($palavras)); $n >= 5; $n--) {
            $janela = implode(' ', array_slice($palavras, 0, $n));
            $literal = $this->validador->localizarTrechoLiteral($textoPdf, $janela);

            if ($literal !== null) {
                return $this->compactarEspacosPreservandoTexto($literal);
            }
        }

        return null;
    }

    /**
     * Remove itens de lista inventados pela IA (ex.: áreas temáticas ausentes no PDF).
     *
     * @param  array<string, mixed>  $metadados
     * @return array<string, mixed>
     */
    private function descartarItensNaoLiterais(string $textoPdf, array $metadados): array
    {
        foreach (['autores', 'palavras_chave', 'areas_tematicas'] as $lista) {
            $itens = $metadados[$lista] ?? null;

            if (! is_array($itens)) {
                continue;
            }

            $validos = [];

            foreach ($itens as $item) {
                $valor = trim((string) $item);

                if ($valor === '') {
                    continue;
                }

                if ($this->validador->localizarTrechoLiteral($textoPdf, $valor) !== null) {
                    $validos[] = $valor;
                }
            }

            $metadados[$lista] = $validos;
        }

        $doi = trim((string) ($metadados['doi'] ?? ''));

        if ($doi !== '') {
            $doiCompacto = preg_replace('/\s+/u', '', $doi) ?? $doi;
            $fonteCompacta = preg_replace('/\s+/u', '', $textoPdf) ?? $textoPdf;

            if ($doiCompacto === '' || ! str_contains(mb_strtolower($fonteCompacta), mb_strtolower($doiCompacto))) {
                $metadados['doi'] = '';
            }
        }

        return $metadados;
    }

    private function compactarEspacosPreservandoTexto(string $texto): string
    {
        $texto = $this->desifenizarTipografico($texto);
        $texto = preg_replace("/[ \t]*\n[ \t]*/u", ' ', $texto) ?? $texto;
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }

    /**
     * Une hifenização tipográfica de PDF ("Cha- gas" / "Cha-\ngas" → "Chagas"),
     * sem alterar hífens lexicais como "laboratory-bred".
     */
    private function desifenizarTipografico(string $texto): string
    {
        // Soft hyphen e hifenização tipográfica de PDF.
        $texto = str_replace(["\u{00AD}", "\xC2\xAD"], '', $texto);
        // Sílabas partidas: "Cha-\ngas" / "Cha- gas" → "Chagas".
        $texto = preg_replace('/(\p{L})[\-\x{2010}\x{2011}]\R(\p{Ll})/u', '$1$2', $texto) ?? $texto;
        $texto = preg_replace('/(\p{L})[\-\x{2010}\x{2011}]\s+(\p{Ll})/u', '$1$2', $texto) ?? $texto;
        // Nomes hifenizados partidos: "Parra-\nHenao" → "Parra-Henao".
        $texto = preg_replace('/(\p{L})[\-\x{2010}\x{2011}]\R(\p{Lu})/u', '$1-$2', $texto) ?? $texto;

        return preg_replace('/(\p{L})[\-\x{2010}\x{2011}]\s+(\p{Lu})/u', '$1-$2', $texto) ?? $texto;
    }

    /**
     * @param  array<string, mixed>  $metadados
     */
    private function resolverInicioCorpoEditorial(string $textoPdf, ?int $posMarcador, array $metadados): ?int
    {
        $posAccepted = $this->posicaoAposAccepted($textoPdf);
        $posKeywords = $this->posicaoAposPalavrasChave($textoPdf);

        // Layout clássico do portal (HTML completo): o corpo editorial recomeça
        // integralmente após "Accepted …". Preferir isso evita o fragmento de
        // coluna que o PDF injeta entre Key words e o bloco lateral.
        if ($posAccepted !== null) {
            return $posAccepted;
        }

        // Layout atual: muitos artigos só têm corpo no PDF e a prosa começa
        // logo após Key words (Accepted fica na lateral com "How to cite").
        if ($posKeywords !== null) {
            return $posKeywords;
        }

        if ($posMarcador !== null) {
            return $posMarcador;
        }

        return $this->inferirInicioCorpo($textoPdf, $metadados);
    }

    private function posicaoAposPalavrasChave(string $textoPdf): ?int
    {
        if (preg_match(
            '/(?:Key words?|Keywords?|Palavras[-\s]?chave)\s*:\s*[^\n]+/iu',
            $textoPdf,
            $m,
            PREG_OFFSET_CAPTURE,
        ) !== 1) {
            return null;
        }

        $fim = (int) $m[0][1] + strlen($m[0][0]);
        $pos = $this->offsetBytesParaMb($textoPdf, $fim);
        $depois = ltrim(mb_substr($textoPdf, $pos));

        if ($depois === '') {
            return null;
        }

        // Layout clássico: após keywords ainda vêm doi / financial / Accepted.
        if (preg_match('/^(doi\s*:|Financial support|Received\b|Accepted\b|How to cite|Handling editor|\+\s*Corresponding)/iu', $depois) === 1) {
            return null;
        }

        // Precisa parecer prosa editorial, não metadado solto.
        if (mb_strlen($depois) < 40) {
            return null;
        }

        return $pos;
    }

    private function posicaoAposAccepted(string $textoPdf): ?int
    {
        if (preg_match('/Accepted\s+\d{1,2}\s+\w+\s+\d{4}\s*/iu', $textoPdf, $m, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        $fimAccepted = (int) $m[0][1] + strlen($m[0][0]);
        $depois = ltrim(substr($textoPdf, $fimAccepted));

        // Coluna lateral moderna: depois de Accepted vem "How to cite" / editor.
        if ($depois !== '' && preg_match('/^(How to cite|Handling editor|License\b|This is an Open Access)/iu', $depois) === 1) {
            return null;
        }

        return $this->offsetBytesParaMb($textoPdf, $fimAccepted);
    }

    private function removerBlocosLateraisIntercalados(string $texto): string
    {
        // Extratores PDF misturam a coluna lateral (doi, support, how to cite) no corpo.
        $texto = preg_replace('/\n?\s*doi:\s*10\.\d{4,}\/[^\n]+/iu', "\n", $texto) ?? $texto;
        // Fragmentos de DOI quebrados pelo layout (ex.: "0 074 - 02760210 015").
        $texto = preg_replace('/\b10\.1590\/\s*[0-9\s\-]+/iu', ' ', $texto) ?? $texto;
        $texto = preg_replace('/(?<=\s)0?\s*\d{0,3}\s*074\s*-\s*02760\d+(?:\s+\d{3})?(?=\s)/u', ' ', $texto) ?? $texto;
        $texto = preg_replace(
            '/Financial support:.*?Accepted\s+\d{1,2}\s+\w+\s+\d{4}/isu',
            ' ',
            $texto,
        ) ?? $texto;
        $texto = preg_replace(
            '/\+\s*Corresponding authors?:[^\n]*/iu',
            ' ',
            $texto,
        ) ?? $texto;
        $texto = preg_replace(
            '/How to cite:.*?Handling editor:[^\n]*/isu',
            ' ',
            $texto,
        ) ?? $texto;
        $texto = preg_replace('/Received\s+\d{1,2}\s+\w+\s+\d{4}/iu', ' ', $texto) ?? $texto;
        $texto = preg_replace('/Accepted\s+\d{1,2}\s+\w+\s+\d{4}/iu', ' ', $texto) ?? $texto;

        return trim(preg_replace('/[ ]{2,}/u', ' ', $texto) ?? $texto);
    }

    /**
     * No layout clássico das Memórias, o extrator PDF joga um pedaço do 1º corpo
     * entre Key words e o bloco lateral (doi/Accepted). Reinsere esse fragmento
     * na costura típica "Chile, in the last years" → texto contínuo como no HTML.
     */
    private function fundirFragmentoDeColuna(string $textoPdf, string $trechoCorpo): string
    {
        $fragmento = $this->extrairFragmentoEntreKeywordsESidebar($textoPdf);

        if ($fragmento === null) {
            return $trechoCorpo;
        }

        $fragCompacto = trim(preg_replace('/\s+/u', ' ', $fragmento) ?? $fragmento);
        $corpoCompacto = preg_replace('/\s+/u', ' ', $trechoCorpo) ?? $trechoCorpo;

        if (mb_strlen($fragCompacto) < 80) {
            return $trechoCorpo;
        }

        $ancora = mb_substr($fragCompacto, 0, 48);

        if ($ancora !== '' && mb_stripos($corpoCompacto, $ancora) !== false) {
            return $trechoCorpo;
        }

        // Costura após remoção do cabeçalho de página: "Place, in the/of the/...".
        if (preg_match(
            '/\b([A-Z][a-z]+),\s+((?:in|of|to|for|with|by|on|at)\s+the\b)/u',
            $trechoCorpo,
            $m,
            PREG_OFFSET_CAPTURE,
        ) !== 1) {
            return $trechoCorpo;
        }

        $offsetContinuacao = (int) $m[2][1];

        if ($offsetContinuacao > 2200) {
            return $trechoCorpo;
        }

        $antes = substr($trechoCorpo, 0, $offsetContinuacao);
        $depois = substr($trechoCorpo, $offsetContinuacao);

        return rtrim($antes).' '.$fragCompacto.' '.$depois;
    }

    private function extrairFragmentoEntreKeywordsESidebar(string $textoPdf): ?string
    {
        if (preg_match(
            '/(?:Key words?|Keywords?)\s*:[^\n]+\s*(.+?)(?=\n\s*doi\s*:|\n\s*Financial support|\n\s*Received\b|\n\s*Accepted\b)/isu',
            $textoPdf,
            $m,
        ) !== 1) {
            return null;
        }

        $fragmento = trim($m[1]);

        if ($fragmento === '' || preg_match('/^(doi\s*:|Financial support|Received\b|Accepted\b)/iu', $fragmento) === 1) {
            return null;
        }

        // Layout moderno: o que vem após Key words JÁ é o início do corpo.
        if ($this->posicaoAposAccepted($textoPdf) === null) {
            return null;
        }

        return $fragmento;
    }

    /**
     * No PDF das Memórias, citações frequentemente caem sozinhas na linha:
     * "Paraguay.\n(8)\nMore..." → "Paraguay.(8) More..." (como no HTML do portal).
     */
    private function normalizarCitacoesDoPdf(string $texto): string
    {
        $texto = preg_replace(
            '/([^\s(\[]|[.!?])\s*\R\((\d+(?:\s*,\s*\d+)*)\)\s*(?=\R|[A-ZÁÉÍÓÚÂÊÔÃÕ“"])/u',
            '$1($2) ',
            $texto,
        ) ?? $texto;

        $texto = preg_replace(
            '/([^\s(\[])\s+\((\d+(?:\s*,\s*\d+)*)\)(?=\s|[.,;:!?])/u',
            '$1($2)',
            $texto,
        ) ?? $texto;

        return $texto;
    }

    private function removerCabecalhosDePagina(string $texto): string
    {
        $texto = preg_replace(
            '/\bMem Inst Oswaldo Cruz,\s*Rio de Janeiro,\s*Vol\.\s*\d+,\s*\d{4}\s+\d+\|\d+\b/iu',
            ' ',
            $texto,
        ) ?? $texto;

        $texto = preg_replace(
            '/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\s+et al\.\s+\d+\|\d+\b/u',
            ' ',
            $texto,
        ) ?? $texto;

        $texto = preg_replace('/\bspe-\s*Mem Inst\b/iu', 'species. ', $texto) ?? $texto;

        return trim(preg_replace('/[ ]{2,}/u', ' ', $texto) ?? $texto);
    }

    /**
     * @param  array<string, mixed>  $metadados
     */
    private function inferirInicioCorpo(string $textoPdf, array $metadados): ?int
    {
        $posAccepted = $this->posicaoAposAccepted($textoPdf);

        if ($posAccepted !== null) {
            return $posAccepted;
        }

        $padroes = [
            '/\bINTRODUCTION\b/u',
            '/\bINTRODUÇÃO\b/iu',
            '/\bINTRODUCAO\b/iu',
            '/\bBACKGROUND\b/u',
            '/\bMETHODS?\b/u',
            '/\bMATERIAL(?:S)? AND METHODS?\b/iu',
        ];

        foreach ($padroes as $padrao) {
            if (preg_match($padrao, $textoPdf, $m, PREG_OFFSET_CAPTURE) === 1) {
                return $this->offsetBytesParaMb($textoPdf, (int) $m[0][1]);
            }
        }

        $resumo = trim((string) ($metadados['resumo'] ?? ''));

        if ($resumo !== '') {
            $posResumo = $this->encontrarPosicao($textoPdf, $resumo);

            if ($posResumo !== null) {
                $depois = $posResumo + mb_strlen($resumo);
                $restante = mb_substr($textoPdf, $depois);
                $restante = preg_replace('/^\s*(keywords?|palavras[-\s]?chave|key words)[^\n]{0,400}/iu', '', $restante) ?? $restante;
                $restante = ltrim($restante);

                if ($restante !== '') {
                    return $depois + (mb_strlen(mb_substr($textoPdf, $depois)) - mb_strlen($restante));
                }
            }
        }

        // Fallback: após o primeiro quinto do documento (metadados iniciais).
        $pos = (int) floor(mb_strlen($textoPdf) * 0.12);

        return $pos > 0 ? $pos : null;
    }

    private function inferirInicioReferencias(string $textoPdf, int $apos): ?int
    {
        $trecho = mb_substr($textoPdf, $apos);

        if (preg_match('/(?:^|\n)\s*(REFERENCES|REFERÊNCIAS|REFERENCIAS|BIBLIOGRAPHY|BIBLIOGRAFIA)\b/iu', $trecho, $m, PREG_OFFSET_CAPTURE) === 1) {
            return $apos + $this->offsetBytesParaMb($trecho, (int) $m[0][1]);
        }

        return null;
    }

    private function offsetBytesParaMb(string $texto, int $byteOffset): int
    {
        return mb_strlen(substr($texto, 0, $byteOffset));
    }

    private function encontrarPosicao(string $haystack, string $needle): ?int
    {
        $literal = $this->validador->localizarTrechoLiteral($haystack, $needle);

        if ($literal === null) {
            return null;
        }

        $pos = mb_stripos($haystack, $literal);

        return $pos === false ? null : $pos;
    }

    private function textoParaHtmlLiteral(string $texto): string
    {
        if ($texto === '') {
            return '<p></p>';
        }

        $texto = $this->desifenizarTipografico($texto);
        $texto = $this->promoverTitulosDeLinha($texto);
        $blocos = preg_split("/\n{2,}/u", $texto) ?: [$texto];

        // PDFs científicos costumam ter só quebra simples de linha: reparte por frases.
        if (count($blocos) <= 2) {
            $continuo = trim(preg_replace("/[ \t]*\n[ \t]*/u", ' ', $texto) ?? $texto);
            $continuo = preg_replace('/[ ]{2,}/u', ' ', $continuo) ?? $continuo;
            $blocos = $this->partirEmParagrafosPorFrase($continuo);
        }

        $html = [];

        foreach ($blocos as $bloco) {
            $bloco = trim($bloco);

            if ($bloco === '') {
                continue;
            }

            $bloco = $this->desifenizarTipografico($bloco);
            $bloco = preg_replace("/[ \t]*\n[ \t]*/u", ' ', $bloco) ?? $bloco;
            $bloco = preg_replace('/[ ]{2,}/u', ' ', $bloco) ?? $bloco;

            if ($bloco === '') {
                continue;
            }

            if ($this->pareceTituloDeSecaoLinha($bloco)) {
                $html[] = '<h2><strong>'.e($bloco).'</strong></h2>';

                continue;
            }

            $html[] = '<p>'.e($bloco).'</p>';
        }

        return $html !== [] ? implode('', $html) : '<p>'.e($this->desifenizarTipografico($texto)).'</p>';
    }

    /**
     * Isola linhas curtas de título (ex.: "Target species") antes de colapsar \n → espaço.
     * Só promove quando a linha anterior fecha frase e a seguinte é prosa longa.
     */
    private function promoverTitulosDeLinha(string $texto): string
    {
        $linhas = preg_split("/\n/u", $texto) ?: [$texto];
        $total = count($linhas);
        $saida = [];

        for ($i = 0; $i < $total; $i++) {
            $trim = trim($linhas[$i]);
            $anterior = $i > 0 ? trim($linhas[$i - 1]) : '';
            $seguinte = $i + 1 < $total ? trim($linhas[$i + 1]) : '';

            $anteriorFecha = $anterior === ''
                || preg_match('/[.!?]$/u', $anterior) === 1
                || preg_match('/\.\s*\(\d/u', $anterior) === 1
                || preg_match('/\)$/u', $anterior) === 1;

            $seguinteProsa = $seguinte !== ''
                && mb_strlen($seguinte) >= 35
                && preg_match('/^[A-ZÀ-Ý]/u', $seguinte) === 1;

            if ($anteriorFecha && $seguinteProsa && $this->pareceTituloDeSecaoLinha($trim)) {
                $saida[] = '';
                $saida[] = $trim;
                $saida[] = '';

                continue;
            }

            $saida[] = $linhas[$i];
        }

        return implode("\n", $saida);
    }

    private function pareceTituloDeSecaoLinha(string $linha): bool
    {
        if ($linha === '' || mb_strlen($linha) < 5 || mb_strlen($linha) > 90) {
            return false;
        }

        if (preg_match('/[.;:!?]/u', $linha) === 1 || substr_count($linha, ',') > 0) {
            return false;
        }

        if (preg_match('/^(\d+[\.\)]|\(|\[\d|doi:|https?:|Fig\.|Table\b)/iu', $linha) === 1) {
            return false;
        }

        // Binômio científico na linha → não é título de seção.
        if (preg_match('/\b[A-Z][a-z]{3,}\s+[a-z]{3,}\b/u', $linha) === 1) {
            return false;
        }

        if (preg_match('/^(ACKNOWLEDGEMENTS|AUTHORS(?:[’\'] CONTRIBUTION)?|REFERENCES|INTRODUCTION|METHODS|RESULTS|DISCUSSION|CONCLUSIONS?|BACKGROUND|OBJECTIVES?|FINDINGS|MAIN CONCLUSIONS?|In conclusion)\b/iu', $linha) === 1) {
            return true;
        }

        // Topônimos / nomes geográficos sozinhos não são seções no HTML do portal.
        if (preg_match('/\b(Republic|Guiana|States|America|Brasil|Brazil|Argentina|Mexico|México|Chile|Peru|Colombia|Venezuela|Bolivia|Paraguay|Uruguay|France|Spain|Africa|Asia|Europe|Island|Province|State|City)\b/i', $linha) === 1) {
            return false;
        }

        if (preg_match('/\b(have|has|had|been|being|was|were|are|is|can|will|would|could|should|their|there|these|those|which|while|where|when|what|as|despite|more|some|most|into|from|with|that|this|also|only|even)\b/i', $linha) === 1) {
            return false;
        }

        $palavras = preg_split('/\s+/u', $linha) ?: [];

        if (count($palavras) < 2 || count($palavras) > 10) {
            return false;
        }

        $stop = 0;

        foreach ($palavras as $palavra) {
            if (preg_match('/^(the|and|of|in|to|for|on|by|or|until|after|before|over)$/i', $palavra) === 1) {
                $stop++;
            }
        }

        // No HTML do portal, subtítulos curtos costumam trazer "until/after/species/vectors".
        $pareceSubtituloPortal = preg_match('/\b(until|after|before|species|vectors|control|diagnosis|treatment|surveillance|challenges|strategies|overview)\b/i', $linha) === 1;

        return preg_match('/^[A-ZÀ-Ý]/u', $linha) === 1 && $stop <= 2 && $pareceSubtituloPortal;
    }

    /**
     * @return list<string>
     */
    private function partirEmParagrafosPorFrase(string $texto): array
    {
        if (mb_strlen($texto) <= 700) {
            return [$texto];
        }

        $frases = preg_split('/(?<=[.!?])\s+(?=[A-ZÁÉÍÓÚÂÊÔÃÕ“"])/u', $texto) ?: [$texto];
        $partes = [];
        $atual = '';

        foreach ($frases as $frase) {
            $frase = trim($frase);

            if ($frase === '') {
                continue;
            }

            $candidato = $atual === '' ? $frase : $atual.' '.$frase;

            if (mb_strlen($candidato) > 620 && $atual !== '') {
                $partes[] = $atual;
                $atual = $frase;

                continue;
            }

            $atual = $candidato;
        }

        if ($atual !== '') {
            $partes[] = $atual;
        }

        return $partes !== [] ? $partes : [$texto];
    }

    /**
     * @return list<string>
     */
    private function referenciasLiterais(string $textoRefs): array
    {
        if ($textoRefs === '') {
            return [];
        }

        $linhas = preg_split("/\n+/u", $textoRefs) ?: [];
        $referencias = [];
        $atual = '';

        foreach ($linhas as $linha) {
            $linha = trim($linha);

            if ($linha === '') {
                if ($atual !== '') {
                    $referencias[] = $atual;
                    $atual = '';
                }

                continue;
            }

            // Cabeçalho da seção não entra como referência.
            if ($atual === '' && preg_match('/^(references|referências|bibliografia)\b/iu', $linha) === 1) {
                continue;
            }

            if ($atual === '') {
                $atual = $linha;

                continue;
            }

            // Nova referência: "1. Autor..." ou "[1] Autor..." — não anos entre parênteses ("1944)").
            if (preg_match('/^(\[\d{1,3}\]|\d{1,3}\.)\s+\S/u', $linha) === 1) {
                $referencias[] = $atual;
                $atual = $linha;

                continue;
            }

            $atual .= ' '.$linha;
        }

        if ($atual !== '') {
            $referencias[] = $atual;
        }

        return array_values(array_filter(
            array_map(function (string $ref): string {
                return $this->compactarEspacosPreservandoTexto($ref);
            }, $referencias),
            static fn (string $ref): bool => $ref !== ''
                && mb_strlen($ref) >= 20
                && preg_match('/^Mem Inst Oswaldo|^online\s*\|/iu', $ref) !== 1
                && preg_match('/^\d{4}\)\s/u', $ref) !== 1,
        ));
    }
}
