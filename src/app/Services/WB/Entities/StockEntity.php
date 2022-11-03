<?php

namespace App\Services\WB\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class StockEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id', 255)->index();
        $table->date('published_at');
        $table->dateTime('lastChangeDate', 3);
        $table->string('supplierArticle', 75)->default("");
        $table->string('techSize', 30)->default("");
        $table->string('barcode', 50)->default("");
        $table->integer('quantity')->default(0);
        $table->boolean('isSupply')->default(false);
        $table->boolean('isRealization')->default(false);
        $table->integer('quantityFull')->default(0);
        $table->integer('quantityNotInOrders')->default(0);
        $table->unsignedBigInteger('warehouse');
        $table->string('warehouseName', 50);
        $table->integer('inWayToClient')->default(0);
        $table->integer('inWayFromClient')->default(0);
        $table->unsignedBigInteger('nmId');
        $table->string('subject', 50)->default("");
        $table->string('category', 50)->default("");
        $table->integer('daysOnSite')->default(0);
        $table->string('brand', 50)->default("");
        $table->string('SCCode', 50)->default("");
        $table->double('Price', 8, 2)->default(0.00);
        $table->integer('Discount')->default(0);
    }
}