<?php

namespace Tests\Unit;

use App\Support\Artigos\MontarArtigoLiteralDoPdf;
use PHPUnit\Framework\TestCase;

class MontarArtigoLiteralDoPdfExtrasTest extends TestCase
{
    public function test_desifeniza_corpo_e_nao_parte_referencia_por_ano_entre_parenteses(): void
    {
        $texto = "Titulo Exato Do Artigo\nAna Souza\n"
            ."Accepted 04 May 2021\n"
            ."Despite the great achievements in controlling Cha-\ngas disease, also known as American Trypanosomiasis.\n"
            .'More body text about vectors and monitoring strategies.'
            .str_repeat(' Continuacao fiel do texto.', 20)
            ."\nREFERENCES\n"
            ."1. Lent H, Wygodzinsky P. Revision of the Triatominae. Bull Am Museum Nat Hist. 1979; 163(3): 123-520.\n"
            ."2. Autor B. Species description of N. flavida (Neiva,\n"
            ."1944) and N. flavida (Neiva, 1911). J Vector Ecol. 2018; 43(1): 148-57.\n"
            .'3. Weeks ENI et al. Community-based sampling. 2014.';

        $resultado = (new MontarArtigoLiteralDoPdf)->montar($texto, [
            'titulo' => 'Titulo Exato Do Artigo',
            'resumo' => '',
            'autores' => ['Ana Souza'],
            'marcador_inicio_corpo' => 'Despite the great achievements in controlling',
            'marcador_inicio_referencias' => 'REFERENCES',
        ]);

        $this->assertStringContainsString('controlling Chagas disease', $resultado['conteudo_html']);
        $this->assertStringNotContainsString('Cha- gas', $resultado['conteudo_html']);
        $this->assertCount(3, $resultado['referencias']);
        $this->assertStringContainsString('1944) and N. flavida', $resultado['referencias'][1]);
        $this->assertStringStartsWith('Weeks', $resultado['referencias'][2]);
    }

    public function test_funde_fragmento_da_coluna_entre_key_words_e_accepted(): void
    {
        $texto = "Titulo Exato Do Artigo\nAna Souza\n"
            ."Abstract: Resumo literal do artigo para fidelidade completa ao original.\n"
            ."Key words: American Trypanosomiasis - vectors - Triatominae\n"
            .'Uruguay, various provinces in Argentina, and several regions in Paraguay. (8) '
            .'More recently the elimination of R. prolixus was recorded in Central America.'
            .str_repeat(' Continuacao do fragmento editorial.', 12)
            ."\ndoi: 10.1590/0 074 - 02760210 015\n"
            ."Financial support: CNPq.\n"
            ."Accepted 04 May 2021\n"
            .'Despite the great achievements in controlling Chagas disease, also known as American Trypanosomiasis. '
            .'The most remarkable achievement was the elimination of T. infestans populations in Brazil, Chile,'."\n"
            ."Jane Costa et al. 2|10\n"
            .'in the last years. Monitoring continues with native vectors.'
            .str_repeat(' Continuacao fiel do texto.', 20)
            ."\nTarget species\n"
            .'As previously mentioned the overview continues with vectors.'
            .str_repeat(' Continuacao fiel do texto.', 10)
            ."\nREFERENCES\n1. Lent H. Revision. 1979.";

        $resultado = (new MontarArtigoLiteralDoPdf)->montar($texto, [
            'titulo' => 'Titulo Exato Do Artigo',
            'resumo' => 'Resumo literal do artigo para fidelidade completa ao original.',
            'autores' => ['Ana Souza'],
            'marcador_inicio_corpo' => 'Uruguay, various provinces in Argentina',
            'marcador_inicio_referencias' => 'REFERENCES',
        ]);

        $textoCorpo = strip_tags($resultado['conteudo_html']);
        $this->assertStringStartsWith('Despite the great achievements', ltrim($textoCorpo));
        $this->assertStringContainsString('Brazil, Chile, Uruguay, various provinces', $textoCorpo);
        $this->assertStringContainsString('in the last years', $textoCorpo);
        $this->assertStringNotContainsString('074 - 02760210', $textoCorpo);
        $this->assertStringContainsString('Target species', $textoCorpo);
    }

    public function test_ignora_marcador_da_ia_no_meio_do_artigo_quando_ha_accepted(): void
    {
        $texto = "Titulo Exato Do Artigo\nAna Souza\n"
            ."Chagas disease persists as one of the most important abstract sentences.\n"
            ."Accepted 04 May 2021\n"
            ."Despite the great achievements in controlling Chagas disease.\n"
            .'Target species'."\n"
            .'As previously mentioned the overview continues with vectors.'
            .str_repeat(' Continuacao fiel do texto.', 20)
            ."\nTriatomines have been known since the 18th century with more detail."
            .str_repeat(' Continuacao fiel do texto.', 10)
            ."\nREFERENCES\n1. Lent H. Revision. 1979.";

        $resultado = (new MontarArtigoLiteralDoPdf)->montar($texto, [
            'titulo' => 'Titulo Exato Do Artigo',
            'resumo' => 'Chagas disease persists as one of the most important abstract sentences.',
            'autores' => ['Ana Souza'],
            'marcador_inicio_corpo' => 'Triatomines have been known since the 18th century',
            'marcador_inicio_referencias' => 'REFERENCES',
        ]);

        $textoCorpo = strip_tags($resultado['conteudo_html']);
        $this->assertStringStartsWith('Despite the great achievements', ltrim($textoCorpo));
        $this->assertStringNotContainsString('Chagas disease persists as one of the most important abstract', $textoCorpo);
        $this->assertStringContainsString('Target species', $textoCorpo);
        $this->assertStringContainsString('Triatomines have been known', $textoCorpo);
    }

    public function test_corpo_comeca_apos_key_words_quando_accepted_precede_how_to_cite(): void
    {
        $texto = "Titulo Exato Do Artigo Moderno\nAna Souza\n"
            ."BACKGROUND Extracellular vesicles are involved in pathogenesis of infection.\n"
            ."OBJECTIVES We analysed concentration and size in plasma samples.\n"
            ."METHODS Plasma samples from patients were analysed carefully.\n"
            ."FINDINGS NTA showed an increased concentration of microparticles.\n"
            ."MAIN CONCLUSIONS Our findings suggest that EVs harboured viral components.\n"
            ."Key words: extracellular vesicles - SARS-CoV-2 - COVID-19\n"
            ."Since 2003, coronaviruses have been the cause of serious and fatal diseases.\n"
            .'Continuacao editorial sobre patogenese e desfechos clinicos.'
            .str_repeat(' Continuacao fiel do texto.', 25)
            ."\ndoi: 10.1590/0074-02760250109\n"
            ."Financial support: FAPESP.\n"
            ."+ Corresponding authors: ana@example.com\n"
            ."Received 29 April 2025\n"
            ."Accepted 26 September 2025\n"
            ."How to cite: Souza A. Extracellular vesicles. Mem Inst Oswaldo Cruz. 2026.\n"
            ."Handling editor: Adeilton Alves Brandão\n"
            .'More body text after sidebar remains part of the article discussion.'
            .str_repeat(' Continuacao fiel do texto.', 10)
            ."\nREFERENCES\n1. Tay MZ. The trinity of COVID-19. 2020.";

        $resultado = (new MontarArtigoLiteralDoPdf)->montar($texto, [
            'titulo' => 'Titulo Exato Do Artigo Moderno',
            'resumo' => 'BACKGROUND Extracellular vesicles are involved in pathogenesis of infection. OBJECTIVES We analysed concentration and size in plasma samples. METHODS Plasma samples from patients were analysed carefully. FINDINGS NTA showed an increased concentration of microparticles. MAIN CONCLUSIONS Our findings suggest that EVs harboured viral components.',
            'autores' => ['Ana Souza'],
            'marcador_inicio_corpo' => 'Since 2003, coronaviruses have been the cause',
            'marcador_inicio_referencias' => 'REFERENCES',
        ]);

        $textoCorpo = strip_tags($resultado['conteudo_html']);
        $this->assertStringStartsWith('Since 2003, coronaviruses', ltrim($textoCorpo));
        $this->assertStringNotContainsString('How to cite', $textoCorpo);
        $this->assertStringNotContainsString('Handling editor', $textoCorpo);
        $this->assertStringNotContainsString('Financial support', $textoCorpo);
        $this->assertStringContainsString('More body text after sidebar', $textoCorpo);
        $this->assertNotEmpty($resultado['referencias']);
    }
}
