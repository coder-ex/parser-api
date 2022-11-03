<?php

namespace App\Services\Roistat\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class ListIntegrationEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id')->index();
        $table->string('order_id')->nullable();
        $table->string('status_name')->nullable();
        $table->string('display_name', 1000)->nullable();
        $table->string('system_name', 1000)->nullable();
        $table->datetime('creation_date')->index()->nullable(); // индексированное поле
        $table->double('revenue', 15, 2)->nullable();
        $table->string('client_id')->nullable();
        $table->string('roistat')->nullable();
        $table->string('fields_manager')->nullable();
        $table->string('fields_in_prior')->nullable();
        $table->string('fields_work_prior')->nullable();
        $table->string('fields_target_lead')->nullable();
        $table->string('marker', 1000)->nullable();

        $table->uuid('marker_id')->nullable();
        $table->foreign('marker_id')->references('id')->on("{$this->nameAPI}_{$this->name}_{$this->tplFK}")->onDelete('cascade');
    }
}