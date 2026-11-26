<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('codigo', 20)->unique();
            $table->unsignedSmallInteger('carga_horaria')->nullable();
            $table->timestamps();
        });

        Schema::create('turmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escola_id')->constrained('escolas')->restrictOnDelete();
            $table->string('nome', 60);
            $table->string('serie', 40);
            $table->string('turno', 20);
            $table->unsignedSmallInteger('ano_letivo');
            $table->timestamps();
            $table->unique(['escola_id', 'nome', 'ano_letivo']);
        });

        Schema::create('alunos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escola_id')->constrained('escolas')->restrictOnDelete();
            $table->string('nome');
            $table->string('codigo', 20)->unique();
            $table->date('data_nascimento');
            $table->string('responsavel')->nullable();
            $table->timestamps();
        });

        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('alunos')->cascadeOnDelete();
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_chamada')->nullable();
            $table->string('situacao', 20)->default('ativa');
            $table->date('data_matricula');
            $table->timestamps();
            $table->unique(['aluno_id', 'turma_id']);
        });

        // Vínculo professor ↔ turma/disciplina
        Schema::create('alocacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $table->foreignId('disciplina_id')->constrained('disciplinas')->restrictOnDelete();
            $table->foreignId('professor_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['turma_id', 'disciplina_id']);
            $table->index('professor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alocacoes');
        Schema::dropIfExists('matriculas');
        Schema::dropIfExists('alunos');
        Schema::dropIfExists('turmas');
        Schema::dropIfExists('disciplinas');
    }
};
