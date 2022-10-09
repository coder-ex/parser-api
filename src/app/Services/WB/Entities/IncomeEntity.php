<?php

namespace App\Services\WB\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class IncomeEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id', 255);
        $table->bigInteger('incomeId')->nullable();
        $table->string('number', 40)->nullable();
        $table->dateTime('date', 0);
        $table->dateTime('lastChangeDate', 3);
        $table->string('supplierArticle', 75)->nullable();
        $table->string('techSize', 30)->nullable();
        $table->string('barcode', 30)->nullable();
        $table->integer('quantity')->nullable();
        $table->double('totalPrice', 8, 2)->default(0.00);
        $table->dateTime('dateClose', 0);
        $table->string('warehouseName', 50)->nullable();
        $table->unsignedBigInteger('nmId')->nullable();
        $table->string('status', 50)->nullable();
    }
}