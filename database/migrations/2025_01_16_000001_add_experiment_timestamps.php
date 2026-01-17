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
        Schema::table('evolve_experiments', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('is_active');
            $table->timestamp('stopped_at')->nullable()->after('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evolve_experiments', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'stopped_at']);
        });
    }
};
