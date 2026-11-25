<?php

namespace Tests\Unit;

use App\Support\Artigos\NormalizarTextoReferencia;
use PHPUnit\Framework\TestCase;

class NormalizarTextoReferenciaTest extends TestCase
{
    public function test_remove_numeracao_inicial_sem_alterar_o_restante(): void
    {
        $normalizar = new NormalizarTextoReferencia;

        $this->assertSame(
            'Dale C, Almeida CE. Title. 2019.',
            $normalizar->normalizar('71. Dale C, Almeida CE. Title. 2019.'),
        );
        $this->assertSame(
            'Dale C, Almeida CE. Title. 2019.',
            $normalizar->normalizar('71 Dale C, Almeida CE. Title. 2019.'),
        );
        $this->assertSame(
            'Aragão MB. Domiciliação de triatomíneos.',
            $normalizar->normalizar('96. Aragão MB. Domiciliação de triatomíneos.'),
        );
        $this->assertSame(
            'Autor A. Titulo. 2020.',
            $normalizar->normalizar('[1] Autor A. Titulo. 2020.'),
        );
        $this->assertSame(
            'Autor A. Titulo. 2020.',
            $normalizar->normalizar('01. Autor A. Titulo. 2020.'),
        );
    }
}
