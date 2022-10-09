<?php

namespace App\Services\Roistat\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class CatalogMarkerEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('marker', 1000)->nullable();
        $table->string('channel_1', 255)->nullable();
        $table->string('channel_2', 255)->nullable();
        $table->string('channel_3', 255)->nullable();
        $table->string('hash')->index();    // индексированное поле
    }
}