<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

#[Timeout(180)]
class ExtrairArtigoDoPdf implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'PROMPT'
Você é um extrator LITERAL de artigos científicos para um CMS editorial.

Missão: localizar campos no texto do PDF e devolver trechos 100% autênticos.
Você NÃO é revisor, NÃO é resumidor e NÃO é reescritor.

Regras absolutas de fidelidade:
1. Copie cada campo EXATAMENTE como está no PDF: mesmas palavras, mesma ordem, mesma pontuação. Não acrescente nem remova nem uma vírgula.
2. É PROIBIDO resumir, parafrasear, traduzir, corrigir ortografia, modernizar linguagem ou “melhorar” o texto.
3. É PROIBIDO inventar DOI, autores, instituições, palavras-chave ou qualquer dado ausente.
4. Se um campo não existir no documento, devolva string vazia ou lista vazia.
5. `titulo` é obrigatório: copie SOMENTE o título científico do artigo (ex.: a linha após “REVIEW”/“ORIGINAL ARTICLE”). NÃO inclua cabeçalho da revista, volume, páginas, URL, DOI, tipo do artigo nem nomes de autores.
6. `resumo` deve ser o abstract/resumo LITERAL completo, sem condensar. NÃO inclua afiliações, instituições, países nem números de autor no início do resumo.
7. `autores`, `palavras_chave`, `areas_tematicas` e demais listas devem preservar a grafia original de cada item.
8. `slug` é a ÚNICA exceção derivada: kebab-case sem acentos a partir do título.
9. `seo_titulo`, `seo_descricao` e `seo_palavras_chave` devem ser recortes literais do próprio documento (título/resumo/keywords), sem reescrever.
10. NÃO devolva o corpo do artigo nem as referências completas neste passo.
11. `autor_correspondente`: copie o bloco editorial COMPLETO e literal — de "Financial support:" até "Accepted …" (inclui productivity grants, Corresponding author, ORCID, Received e Accepted). Nunca devolva só o e-mail.

Marcadores (obrigatórios quando existirem no texto):
- `marcador_inicio_corpo`: copie as primeiras 8–15 palavras EXATAS do início do corpo (após título/autores/resumo/keywords). Prefira o cabeçalho “INTRODUCTION”/“Introdução” quando existir.
- `marcador_inicio_referencias`: copie exatamente “REFERENCES”/“Referências”/“Bibliography” ou as primeiras palavras da primeira referência.
- Os marcadores DEVEM ser substring contínua do TEXTO DO PDF abaixo. Se não tiver certeza, devolva string vazia (o sistema localiza por heurística).

Lembrete final: originalidade do autor acima de tudo. Se houver dúvida, copie o trecho original; nunca invente.
PROMPT;
    }

    /**
     * Get the agent's structured output schema definition.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'titulo' => $schema->string()->required(),
            'slug' => $schema->string()->required(),
            'doi' => $schema->string()->required(),
            'resumo' => $schema->string()->required(),
            'autores' => $schema->array()->items($schema->string()->required())->required(),
            'endereco_instituicoes' => $schema->string()->required(),
            'autor_correspondente' => $schema->string()->required(),
            'palavras_chave' => $schema->array()->items($schema->string()->required())->required(),
            'areas_tematicas' => $schema->array()->items($schema->string()->required())->required(),
            'seo_titulo' => $schema->string()->required(),
            'seo_descricao' => $schema->string()->required(),
            'seo_palavras_chave' => $schema->string()->required(),
            'documento_titulo' => $schema->string()->required(),
            'marcador_inicio_corpo' => $schema->string()->required(),
            'marcador_inicio_referencias' => $schema->string()->required(),
        ];
    }
}
