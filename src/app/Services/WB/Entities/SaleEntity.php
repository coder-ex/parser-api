<?php

namespace App\Services\WB\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class SaleEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id', 255);
        $table->dateTime('date', 0);
        $table->dateTime('lastChangeDate', 3);
        $table->string('supplierArticle', 75)->nullable();
        $table->string('techSize', 30)->nullable();
        $table->string('barcode', 30)->nullable();
        $table->double('totalPrice', 8, 2)->default(0.00);
        $table->integer('discountPercent')->default(0);
        $table->boolean('isSupply')->default(false);
        $table->boolean('isRealization')->default(false);
        $table->integer('promoCodeDiscount')->default(0);
        $table->string('warehouseName', 50)->nullable();
        $table->string('countryName', 200)->nullable();
        $table->string('oblastOkrugName', 200)->nullable();
        $table->string('regionName', 200)->nullable();
        $table->bigInteger('incomeID')->nullable();
        $table->string('saleID', 20)->nullable();
        $table->string('status', 20)->nullable();
        $table->bigInteger('odid')->nullable();
        $table->integer('spp')->default(0);
        $table->double('forPay', 8, 2)->default(0.00);
        $table->double('finishedPrice', 8, 2)->default(0.00);
        $table->double('priceWithDisc', 8, 2)->default(0.00);
        $table->bigInteger('nmId')->nullable();
        $table->string('subject', 50)->nullable();
        $table->string('category', 50)->nullable();
        $table->string('brand', 50)->nullable();
        $table->integer('IsStorno')->default(0);
        $table->string('gNumber', 50)->nullable();
        $table->string('sticker', 50)->nullable();
    }
}