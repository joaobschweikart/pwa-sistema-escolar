<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // UUID da operação gerado no cliente = chave única (idempotência do lote)
        Schema::create('sync_operacoes', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 40);
            $table->string('status', 20);
            $table->json('resultado')->nullable();
            $table->timestamps(3);
            $table->index(['usuario_id', 'created_at']);
        });

        // Fila de conflitos de nota para decisão do gestor
        Schema::create('conflitos_nota', function (Blueprint $table) {
            $table->id();
            $table->uuid('operacao_uuid')->unique();
            $table->foreignId('avaliacao_id')->constrained('avaliacoes')->cascadeOnDelete();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->foreignId('professor_id')->constrained('users')->restrictOnDelete();
            $table->decimal('valor_servidor', 4, 2);
            $table->decimal('valor_proposto', 4, 2);
            $table->unsignedInteger('versao_base');
            $table->unsignedInteger('versao_servidor');
            $table->dateTime('proposto_em', 3);
            $table->string('status', 20)->default('pendente')->index();
            $table->string('resolucao', 30)->nullable();
            $table->foreignId('resolvido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('resolvido_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conflitos_nota');
        Schema::dropIfExists('sync_operacoes');
    }
};
