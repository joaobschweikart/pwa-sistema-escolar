<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A chave da aula é um UUID gerado no cliente (criação offline sem colisão)
        Schema::create('aulas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('alocacao_id')->constrained('alocacoes')->cascadeOnDelete();
            $table->date('data');
            $table->text('conteudo');
            $table->text('observacoes')->nullable();
            $table->unsignedInteger('versao')->default(1);
            $table->dateTime('alterado_em', 3);
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps(3);
            $table->index(['alocacao_id', 'data']);
        });

        Schema::create('frequencias', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('aula_id')->constrained('aulas')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->boolean('presente');
            $table->unsignedInteger('versao')->default(1);
            $table->dateTime('alterado_em', 3);
            $table->timestamps(3);
            $table->unique(['aula_id', 'matricula_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frequencias');
        Schema::dropIfExists('aulas');
    }
};
