<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alocacao_id')->constrained('alocacoes')->cascadeOnDelete();
            $table->string('titulo', 120);
            $table->string('descricao')->nullable();
            $table->date('data');
            $table->unsignedTinyInteger('periodo')->default(1);
            $table->decimal('peso', 4, 2)->default(1);
            $table->timestamps();
            $table->index(['alocacao_id', 'periodo']);
        });

        Schema::create('notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avaliacao_id')->constrained('avaliacoes')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->decimal('valor', 4, 2);
            $table->unsignedInteger('versao')->default(1);
            $table->dateTime('alterado_em', 3);
            $table->foreignId('lancado_por')->constrained('users')->restrictOnDelete();
            $table->timestamps(3);
            $table->unique(['avaliacao_id', 'matricula_id']);
        });

        // Auditoria imutável de toda alteração de nota (quem, quando, valor antigo/novo)
        Schema::create('auditoria_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nota_id')->nullable()->constrained('notas')->nullOnDelete();
            $table->unsignedBigInteger('avaliacao_id');
            $table->unsignedBigInteger('matricula_id');
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->decimal('valor_anterior', 4, 2)->nullable();
            $table->decimal('valor_novo', 4, 2);
            $table->string('origem', 30);
            $table->uuid('operacao_uuid')->nullable();
            $table->dateTime('ocorrido_em', 3);
            $table->index(['avaliacao_id', 'matricula_id']);
            $table->index('ocorrido_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_notas');
        Schema::dropIfExists('notas');
        Schema::dropIfExists('avaliacoes');
    }
};
