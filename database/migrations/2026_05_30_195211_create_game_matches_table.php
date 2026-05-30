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
        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('series_id')->constrained()->cascadeOnDelete();
            $table->integer('match_number');
            $table->json('blue_bans')->nullable();
            $table->json('red_bans')->nullable();
            $table->json('blue_picks')->nullable();
            $table->json('red_picks')->nullable();
            $table->json('priorities_selected')->nullable();
            $table->integer('current_turn_index')->default(0);
            $table->string('status')->default('drafting');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_matches');
    }
};
