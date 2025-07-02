<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LicitacaoItem extends Model
{
     use HasFactory;

    protected $fillable = [
        'licitacao_id',
        'item_numero',
        'descricao',
        'quantidade',
        'unidade',
    ];

    public function licitacao()
    {
        return $this->belongsTo(Licitacao::class);
    }
}
