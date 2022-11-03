<?php

namespace App\Services\WB\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class ExciseReportEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->unsignedBigInteger('id');
        $table->string('project_id', 255)->index();
        $table->string('inn', 50)->nullable();
        $table->double('finishedPrice', 8, 2)->default(0.00);
        $table->integer('operationTypeId')->default(0);
        $table->dateTime('fiscalDt');
        $table->integer('docNumber')->nullable();
        $table->string('fnNumber', 50)->nullable();
        $table->string('regNumber', 50)->nullable();
        $table->string('excise', 255)->nullable();
        $table->dateTime('date', 3);
    }
}