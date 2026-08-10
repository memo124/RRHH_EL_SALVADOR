<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('AUDITORIA', function (Blueprint $table) {
            $table->bigIncrements('ID_AUDITORIA');
            $table->integer('ID_USUARIO')->nullable();
            $table->string('TABLA', 100);
            $table->string('ID_REGISTRO', 50)->nullable();
            $table->string('ACCION', 20);
            $table->json('BEFORE_JSON')->nullable();
            $table->json('AFTER_JSON')->nullable();
            $table->string('IP', 45)->nullable();
            $table->timestamp('FECHA')->useCurrent();

            $table->foreign('ID_USUARIO')->references('ID_USUARIO')->on('USUARIO')->onDelete('set null');
            $table->index(['TABLA', 'ID_REGISTRO']);
            $table->index('FECHA');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('AUDITORIA');
    }
};
