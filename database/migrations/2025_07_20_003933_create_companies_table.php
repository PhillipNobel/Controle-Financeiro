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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cnpj', 18)->unique()->nullable();
            $table->string('razao_social')->nullable();
            $table->string('inscricao_estadual', 50)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->text('endereco')->nullable();
            $table->string('email')->nullable();
            $table->string('pessoa_responsavel')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
