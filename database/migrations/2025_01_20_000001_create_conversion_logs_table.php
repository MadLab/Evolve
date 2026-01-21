<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolve_conversion_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variant_id')->constrained('evolve_variants')->cascadeOnDelete();
            $table->string('conversion_name');
            $table->nullableMorphs('loggable');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['variant_id', 'conversion_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolve_conversion_logs');
    }
};