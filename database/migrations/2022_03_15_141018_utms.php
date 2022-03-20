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
        Schema::create('utms', function (Blueprint $table){
            $table->uuid('id')->unique()->primary();
            $table->uuid('client_id');
            $table->uuid('utm_template_id');
            $table->string('entity_id');
            $table->string('entity_name');
            $table->string('campaign')->nullable();
            $table->string('medium')->nullable();
            $table->string('source')->nullable();
            $table->string('term')->nullable();
            $table->string('content')->nullable();
            $table->timestamp('capture_date');
            $table->boolean('active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('utms');
    }
};
