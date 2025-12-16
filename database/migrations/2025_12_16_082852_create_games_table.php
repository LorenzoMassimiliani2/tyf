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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('code', 8)->unique();
            $table->enum('status', ['lobby', 'active', 'finished'])->default('lobby');
            $table->unsignedInteger('total_turns')->default(12);
            $table->unsignedTinyInteger('starting_difficulty')->default(1);
            $table->unsignedInteger('difficulty_step_turns')->default(3);
            $table->unsignedInteger('current_turn_number')->default(1);
            $table->foreignId('host_player_id')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
