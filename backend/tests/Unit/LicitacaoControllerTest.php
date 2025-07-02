<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Licitacao;
use Illuminate\Http\Request;
use Mockery;
use Illuminate\Pagination\LengthAwarePaginator;

class LicitacaoControllerTest extends TestCase
{

     public function test_api_licitacoes_retorna_sucesso_quando_tem_conteudo()
    {
        $response = $this->get('http://localhost:8000/api/licitacoes');

        $response->assertStatus(200);
        
    }

    public function test_ha_licitacoes_cadastradas()
    {
        $count = Licitacao::count();

        $this->assertTrue($count > 0, "Não há licitações cadastradas no banco de dados.");
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }


    
}
