<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_questions', function (Blueprint $table) {
            $table->index(['room_id', 'status']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->index(['room_id', 'current_score']);
        });

        Schema::table('score_logs', function (Blueprint $table) {
            $table->index('room_id');
        });
    }

    public function down(): void
    {
        Schema::table('room_questions', function (Blueprint $table) {
            $table->dropIndex(['room_id', 'status']);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex(['room_id', 'current_score']);
        });

        Schema::table('score_logs', function (Blueprint $table) {
            $table->dropIndex('room_id');
        });
    }
};