<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('licitacoes', function (Blueprint $table) {
            $table->id();
             $table->string('uasg_codigo');
            $table->string('modalidade')->nullable();
            $table->string('numero')->nullable();
            $table->string('lei')->nullable();
            $table->text('objeto')->nullable();
            $table->date('data_abertura')->nullable();
            $table->string('endereco')->nullable();
            $table->string('municipio')->nullable();
            $table->string('uf')->nullable();
            $table->date('data_entrega_proposta')->nullable();
            $table->string('edital_link')->nullable();
            $table->string('situacao')->nullable()->default('0');
            $table->string('telefone')->nullable();
            $table->timestamps();
  
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licitacoes');
    }
};
