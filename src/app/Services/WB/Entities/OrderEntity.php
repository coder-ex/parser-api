<?php

namespace App\Services\WB\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class OrderEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id', 255);
        $table->dateTime('date', 0)->nullable();
        $table->dateTime('lastChangeDate', 3);
        $table->string('supplierArticle', 75)->nullable();
        $table->string('techSize', 30)->nullable();
        $table->string('barcode', 30)->nullable();
        $table->double('totalPrice', 8, 2)->nullable();
        $table->integer('discountPercent')->nullable();
        $table->string('warehouseName', 50)->nullable();
        $table->string('oblast', 200)->nullable();
        $table->bigInteger('incomeID')->nullable();
        $table->bigInteger('odid')->nullable();
        $table->bigInteger('nmId')->nullable();
        $table->string('subject', 50)->nullable();
        $table->string('category', 50)->nullable();
        $table->string('brand', 50)->nullable();
        $table->boolean('isCancel')->nullable();
        $table->dateTime('cancel_dt', 0)->nullable();
        $table->string('gNumber', 50)->nullable();
        $table->string('sticker', 50)->nullable();

        $table->unique(['odid', 'date'], 'fk_panfilov_orders_odid_date');
    }
}
