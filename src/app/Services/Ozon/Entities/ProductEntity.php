<?php

namespace App\Services\Ozon\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class ProductEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->bigInteger('sku')->nullable();
        $table->string('name', 255)->nullable();
        $table->integer('quantity')->nullable();
        $table->string('offer_id', 255)->nullable();
        $table->string('price', 15)->nullable();
        $table->jsonb('digital_codes')->nullable();

        $table->uuid('fk_product_id')->nullable();
        $table->foreign('fk_product_id')->references('id')->on("{$this->nameAPI}_{$this->name}_{$this->tplFK}")->onDelete('cascade');
    }
}