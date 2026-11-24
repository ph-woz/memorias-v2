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

        if ($posCorpo === null) {
            $posCorpo = $this->inferirInicioCorpo($textoPdf, $metadados);
        }

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

        $metadados['conteudo_html'] = $this->textoParaHtmlLiteral(trim($trechoCorpo));
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
        // Sílabas partidas: "Cha-\ngas" → "Chagas".
        $texto = preg_replace('/(\p{L})-\R(\p{Ll})/u', '$1$2', $texto) ?? $texto;
        $texto = preg_replace('/(\p{L})-\s+(\p{Ll})/u', '$1$2', $texto) ?? $texto;
        // Nomes hifenizados partidos: "Parra-\nHenao" → "Parra-Henao".
        $texto = preg_replace('/(\p{L})-\R(\p{Lu})/u', '$1-$2', $texto) ?? $texto;

        return preg_replace('/(\p{L})-\s+(\p{Lu})/u', '$1-$2', $texto) ?? $texto;
    }

    /**
     * @param  array<string, mixed>  $metadados
     */
    private function inferirInicioCorpo(string $textoPdf, array $metadados): ?int
    {
        // Layout Memórias: corpo começa após "Accepted DD Month YYYY".
        if (preg_match('/Accepted\s+\d{1,2}\s+\w+\s+\d{4}\s+/iu', $textoPdf, $m, PREG_OFFSET_CAPTURE) === 1) {
            $fimAccepted = (int) $m[0][1] + strlen($m[0][0]);

            return $this->offsetBytesParaMb($textoPdf, $fimAccepted);
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

            $html[] = '<p>'.e($bloco).'</p>';
        }

        return $html !== [] ? implode('', $html) : '<p>'.e($this->desifenizarTipografico($texto)).'</p>';
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
