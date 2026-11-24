<?php

namespace App\Support\Artigos;

class VinculaCitacoesNoConteudo
{
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

        return (string) preg_replace_callback(
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
    }

    /**
     * @return list<int>
     */
    public function extrairNumeros(string $texto): array
    {
        preg_match_all('/\d+/', html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8'), $matches);

        $numeros = [];

        foreach ($matches[0] as $match) {
            $numero = (int) $match;

            if ($numero > 0) {
                $numeros[] = $numero;
            }
        }

        return array_values(array_unique($numeros));
    }

    /**
     * @param  list<int>  $numeros
     */
    public function formatarRotulo(array $numeros): string
    {
        return implode(', ', $numeros);
    }
}
