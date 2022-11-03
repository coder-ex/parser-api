<?php

namespace App\Services\Ozon\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class ReportStockEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id')->index();
        $table->date('published_at');
        $table->string('article', 50)->index();
        $table->unsignedBigInteger('product_id')->nullable();
        $table->unsignedBigInteger('sku_id')->index();
        $table->string('product_name')->nullable();
        $table->unsignedBigInteger('barcode')->index();
        $table->string('product_status', 25)->nullable();
        $table->string('site_visibility', 3)->nullable();
        $table->unsignedBigInteger('total_warehouse')->nullable();
        $table->unsignedBigInteger('total_reserv')->nullable();
    }
}