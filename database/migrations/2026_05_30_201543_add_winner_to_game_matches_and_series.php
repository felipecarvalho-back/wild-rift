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
        Schema::table('game_matches', function (Blueprint $table) {
            $table->string('winner_team')->nullable();
        });
        Schema::table('series', function (Blueprint $table) {
            $table->string('winner_team')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_matches', function (Blueprint $table) {
            $table->dropColumn('winner_team');
        });
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn('winner_team');
        });
    }
};
