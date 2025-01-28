<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evolve_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\MadLab\Evolve\Models\Evolve::class, 'experiment_id');
            $table->string('hash');
            $table->longText('content');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['experiment_id', 'hash']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evolve_variants');
    }
};
