<?php

namespace App\Services\Ozon\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class CatalogReportStockEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('name')->index();
        $table->integer('value')->default(0);

        $table->uuid("fk_{$this->nameAPI}_{$this->name}_report_stocks_id")->nullable();
        $table->foreign("fk_{$this->nameAPI}_{$this->name}_report_stocks_id")->references('id')->on("{$this->nameAPI}_{$this->name}_{$this->tplFK}")->onDelete('cascade');
    }
}