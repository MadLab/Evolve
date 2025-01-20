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
        Schema::create('evolve_views', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\MadLab\Evolve\Models\Evolve::class, 'experiment_id');
            $table->string('variant');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('conversions')->default(0);
            $table->timestamps();
            $table->unique(['experiment_id', 'variant']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evolve_views');
    }
};
