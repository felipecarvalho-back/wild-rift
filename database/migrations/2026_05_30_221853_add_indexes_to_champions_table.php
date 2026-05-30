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
        Schema::table('champions', function (Blueprint $table) {
            $table->index('name');
            $table->index('role');
            $table->index('secondary_role');
            $table->index('is_priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('champions', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['role']);
            $table->dropIndex(['secondary_role']);
            $table->dropIndex(['is_priority']);
        });
    }
};
