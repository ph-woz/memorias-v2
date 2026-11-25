<?php

namespace App\Support\Artigos;

class VinculaCitacoesNoConteudo
{
    public function __construct(
        private readonly NormalizarCitacoesNoHtml $normalizarCitacoes = new NormalizarCitacoesNoHtml,
    ) {}

    /**
     * Transforma marcações de citação no HTML do artigo em botões clicáveis
     * que abrem o painel lateral de referências (números 1-based).
     */
    public function __invoke(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return $html;
        }

        // Preserva botões já vinculados para não aninhar <button> ao reprocessar.
        $protegidos = [];
        $html = (string) preg_replace_callback(
            '/<button\b[^>]*\bcitacao-ref\b[^>]*>.*?<\/button>/is',
            function (array $ocorrencia) use (&$protegidos): string {
                $chave = '<!--CITACAO_REF_'.count($protegidos).'-->';
                $protegidos[$chave] = $ocorrencia[0];

                return $chave;
            },
            $html,
        );

        // Garante <sup>(n, m)</sup> limpo antes de virar botão (remove anos/páginas colados).
        $html = $this->normalizarCitacoes->normalizar($html);

        $html = (string) preg_replace_callback(
            '/<sup\b([^>]*)>(.*?)<\/sup>/is',
            function (array $ocorrencia): string {
                $atributos = $ocorrencia[1] ?? '';
                $interno = $ocorrencia[2] ?? '';

                if (
                    str_contains($atributos, 'citacao-ref')
                    || str_contains($interno, 'citacao-ref')
                ) {
                    return $ocorrencia[0];
                }

                $numeros = $this->extrairNumeros($interno);

                if ($numeros === []) {
                    return $ocorrencia[0];
                }

                $refs = implode(',', $numeros);
                $rotulo = $this->formatarRotulo($numeros);

                return sprintf(
                    '<button type="button" class="citacao-ref" data-refs="%s" aria-label="Abrir referência %s"><sup>%s</sup></button>',
                    e($refs),
                    e($rotulo),
                    e($rotulo),
                );
            },
            $html,
        );

        return str_replace(array_keys($protegidos), array_values($protegidos), $html);
    }

    /**
     * @return list<int>
     */
    public function extrairNumeros(string $texto): array
    {
        $extraidos = $this->normalizarCitacoes->extrairNumerosCitacao($texto);

        return array_values(array_map(
            static fn (string $n): int => (int) $n,
            $extraidos,
        ));
    }

    /**
     * @param  list<int>  $numeros
     */
    public function formatarRotulo(array $numeros): string
    {
        return implode(', ', $numeros);
    }
}
