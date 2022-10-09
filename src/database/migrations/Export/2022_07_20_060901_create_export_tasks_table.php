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
        Schema::create('export_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('task', 50);
            $table->string('dateFrom', 25);
            $table->string('start_time', 10);
            // $table->integer('flag', false, true)->default(0);
            // $table->integer('limit', false, true)->default(10000);
            $table->jsonb('extended_fields')->nullable();
            $table->string('url', 1000);
            //$table->string('class', 255);
            $table->string('table', 255);

            $table->uuid('service_id')->nullable();
            $table->foreign('service_id')->references('id')->on('export_services')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('export_tasks');
    }
};
