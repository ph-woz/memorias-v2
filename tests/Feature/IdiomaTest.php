<?php

namespace Tests\Feature;

use App\Enums\Idioma;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IdiomaTest extends TestCase
{
    use RefreshDatabase;

    public function test_primeira_visita_usa_accept_language_do_navegador(): void
    {
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Latest publications')
            ->assertPlainCookie((string) config('localization.cookie'), Idioma::EnUs->value);

        $this->assertSame(Idioma::EnUs->value, session(config('localization.session_key')));
    }

    public function test_primeira_visita_em_espanhol_detecta_es(): void
    {
        $this->withHeader('Accept-Language', 'es-ES,es;q=0.8')
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Últimas publicaciones')
            ->assertPlainCookie((string) config('localization.cookie'), Idioma::EsEs->value);
    }

    public function test_sem_preferencia_cai_no_padrao_pt_br(): void
    {
        $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9')
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Últimas publicações')
            ->assertPlainCookie((string) config('localization.cookie'), Idioma::PtBr->value);
    }

    public function test_cookie_persistido_prevalece_sobre_accept_language(): void
    {
        $this->withHeader('Accept-Language', 'en-US')
            ->withUnencryptedCookie(
                (string) config('localization.cookie'),
                Idioma::EsEs->value,
            )
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Últimas publicaciones');
    }

    public function test_troca_manual_grava_sessao_e_cookie_e_redireciona(): void
    {
        $this->from(route('home'))
            ->post(route('idioma.update'), ['idioma' => Idioma::EnUs->value])
            ->assertRedirect(route('home'))
            ->assertPlainCookie((string) config('localization.cookie'), Idioma::EnUs->value);

        $this->assertSame(Idioma::EnUs->value, session(config('localization.session_key')));

        $this->get(route('home'))
            ->assertOk()
            ->assertSeeText('Latest publications');
    }

    public function test_idioma_invalido_e_rejeitado(): void
    {
        $this->from(route('home'))
            ->post(route('idioma.update'), ['idioma' => 'fr_FR'])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors('idioma');
    }

    public function test_home_exibe_seletor_de_idioma(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('idioma.update'), false)
            ->assertSee('value="'.Idioma::PtBr->value.'"', false)
            ->assertSee('value="'.Idioma::EnUs->value.'"', false)
            ->assertSee('value="'.Idioma::EsEs->value.'"', false);
    }

    public function test_menu_do_site_traduz_rotulos_conhecidos(): void
    {
        MenuItem::factory()->create([
            'rotulo' => 'Artigos',
            'url' => '/#ultimos',
            'ordem' => 1,
            'ativo' => true,
        ]);

        $this->withHeader('Accept-Language', 'en-US')
            ->get(route('home'))
            ->assertOk()
            ->assertSeeText('Articles')
            ->assertDontSeeText('Artigos');
    }
}
