<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_questions', function (Blueprint $table) {
            $table->timestamp('global_timer_expires_at')->nullable()->after('timer_expires_at');
            $table->boolean('is_paused')->default(false)->after('global_timer_expires_at');
            $table->integer('paused_global_remaining')->nullable()->after('is_paused');
            $table->integer('paused_phase_remaining')->nullable()->after('paused_global_remaining');
        });
    }

    public function down(): void
    {
        Schema::table('room_questions', function (Blueprint $table) {
            $table->dropColumn([
                'global_timer_expires_at',
                'is_paused',
                'paused_global_remaining',
                'paused_phase_remaining',
            ]);
        });
    }
};
