<?php

namespace App\Services\Roistat\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class DataEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id')->index();
        $table->double('marketing_cost')->default(0);
        // $table->integer('fields_work_ABC')->default(0);
        $table->datetime('dateFrom')->nullable();
        $table->datetime('dateTo')->index()->nullable();    // индексированное поле

        $table->uuid('marker_id')->nullable();
        $table->foreign('marker_id')->references('id')->on("{$this->nameAPI}_{$this->name}_{$this->tplFK}")->onDelete('cascade');
    }
}