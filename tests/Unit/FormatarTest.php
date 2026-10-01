<?php

namespace Tests\Unit;

use App\Support\Formatar;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class FormatarTest extends TestCase
{
    public function test_mascaras(): void
    {
        $this->assertSame('123.456.789-09', Formatar::cpf('12345678909'));
        $this->assertSame('123.456.789-09', Formatar::cpf('123.456.789-09'));
        $this->assertSame('123', Formatar::cpf('123'));
        $this->assertNull(Formatar::cpf(''));

        $this->assertSame('(86) 99431-1316', Formatar::telefone('86994311316'));
        $this->assertSame('(86) 99431-1316', Formatar::telefone('+55 (86) 99431-1316'));
        $this->assertSame('(86) 3222-1234', Formatar::telefone('8632221234'));

        $this->assertSame('64000-000', Formatar::cep('64000000'));
    }

    public function test_datas_em_pt_br_e_fuso_de_brasilia(): void
    {
        $this->assertSame('02/09/1999', Formatar::data('1999-09-02'));
        // Banco em UTC: 14:35 UTC = 11:35 em Brasília.
        $this->assertSame('01/10/2026 11:35', Formatar::dataHora('2026-10-01 14:35:00'));
        // Digitado 22:00 em Brasília = 01:00 UTC do dia seguinte.
        $this->assertSame('2026-10-02 01:00:00', Formatar::localParaUtc('2026-10-01T22:00')->format('Y-m-d H:i:s'));

        Carbon::setTestNow('2026-10-01 12:00:00');
        $this->assertSame(27, Formatar::idade('1999-09-02'));
        Carbon::setTestNow();

        $this->assertSame('Masculino', Formatar::sexo('masculino'));
        $this->assertSame('Feminino', Formatar::sexo('F'));
    }
}
