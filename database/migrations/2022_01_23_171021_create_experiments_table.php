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
        Schema::create('evolve_experiments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['controller', 'view', 'data']);
            $table->string('trigger');
            $table->json('variants');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evolve_experiments');
    }
};
