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
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turn_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voter_id')->constrained('game_players')->cascadeOnDelete();
            $table->boolean('success')->default(false);
            $table->timestamps();

            $table->unique(['turn_id', 'voter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
