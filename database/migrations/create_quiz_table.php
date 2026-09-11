<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('status', ['waiting', 'ongoing', 'finished'])->default('waiting');
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'operator']);
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('master_questions', function (Blueprint $table) {
            $table->id();
            $table->text('question_text');
            $table->integer('price');
            $table->text('answer_key');
            $table->timestamps();
        });

        Schema::create('room_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('master_question_id')->constrained('master_questions')->cascadeOnDelete();
            $table->enum('status', ['unused', 'active', 'closed'])->default('unused');
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('name', 100);
            $table->integer('current_score')->default(1000);
            $table->timestamps();
        });

        Schema::create('score_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('master_questions')->cascadeOnDelete();
            $table->enum('action_type', ['BUY_DEDUCTION', 'BUY_CORRECT', 'PASS_CORRECT']);
            $table->integer('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('score_logs');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('room_questions');
        Schema::dropIfExists('master_questions');
        Schema::dropIfExists('users');
        Schema::dropIfExists('rooms');
    }
};