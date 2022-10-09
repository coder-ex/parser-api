<?php

namespace App\Services\Ozon\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class StockWarehouseEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->bigInteger('project_id');
        $table->dateTime('date');
        $table->string('offer_id', 255)->nullable();
        $table->bigInteger('product_id')->nullable();
        $table->float('price', 8, 2)->default(0.00);
        $table->bigInteger('fbo_sku')->nullable();
        $table->bigInteger('fbs_sku')->nullable();
        $table->integer('stocks_fbo_present')->default(0);
        $table->integer('stocks_fbs_present')->default(0);
    }
}
