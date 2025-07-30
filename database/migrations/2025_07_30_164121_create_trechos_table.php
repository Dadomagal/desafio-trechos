<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trechos', function (Blueprint $table) {
            $table->id();
            $table->date('data_referencia');
            $table->foreignId('uf_id')->constrained('ufs')->onDelete('cascade');
            $table->foreignId('rodovia_id')->constrained('rodovias')->onDelete('cascade');
            $table->string('tipo_trecho');
            $table->float('quilometragem_inicial');
            $table->float('quilometragem_final');
            $table->json('geo')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trechos');
    }
};
