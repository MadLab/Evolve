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
        Schema::create('evolve_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('evolve_variants')->onDelete('cascade');
            $table->date('date');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('bot_views')->default(0);
            $table->json('conversions')->nullable();
            $table->timestamps();

            $table->unique(['variant_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolve_daily_stats');
    }
};
