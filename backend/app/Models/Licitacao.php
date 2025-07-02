<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @OA\Schema(
 *     schema="Licitacao",
 *     title="Licitação",
 *     description="Modelo de uma licitação",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="uasg_codigo", type="string", example="123456"),
 *     @OA\Property(property="modalidade", type="string", example="Concorrência"),
 *     @OA\Property(property="numero", type="string", example="2023/001"),
 *     @OA\Property(property="situacao", type="string", example="Aberta"),
 *     @OA\Property(property="data_abertura", type="string", format="date", example="2025-07-01"),
 *     @OA\Property(property="endereco", type="string", example="Rua Exemplo, 123"),
 *     @OA\Property(property="municipio", type="string", example="São Paulo"),
 *     @OA\Property(property="uf", type="string", example="SP"),
 *     @OA\Property(property="objeto", type="string", example="Aquisição de equipamentos"),
 *     @OA\Property(property="lei", type="string", example="Lei 8.666/93"),
 *     @OA\Property(property="lida", type="boolean", example=false)
 * )
 */
class Licitacao extends Model
{
    use HasFactory;
 
    protected $table = 'licitacoes';

    protected $fillable = [
        'uasg_codigo',
        'modalidade',
        'numero',
        'situacao',
        'data_abertura',
        'endereco',
        'municipio',
        'uf',
        'objeto',
        'lei',
    ];

    protected $casts = [
        'data_abertura' => 'date',
        'lida' => 'boolean',
    ];

    public function itens()
    {
        return $this->hasMany(LicitacaoItem::class);
    }
}
