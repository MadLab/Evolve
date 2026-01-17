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
            $table->timestamp('last_accessed_at')->nullable()->after('stopped_at');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evolve_experiments', function (Blueprint $table) {
            $table->dropColumn('last_accessed_at');
            $table->dropSoftDeletes();
        });
    }
};
