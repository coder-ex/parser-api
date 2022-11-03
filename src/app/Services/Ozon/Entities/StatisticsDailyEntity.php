<?php

namespace App\Services\Ozon\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class StatisticsDailyEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id');
        $table->string('campaign_id');
        $table->string('name', 1000)->nullable();
        $table->date('date')->index()->nullable();
        $table->integer('showing')->nullable();
        $table->integer('clicks')->nullable();
        $table->double('expense_money', 15, 2)->nullable();
        $table->double('average_money_rate', 15, 2)->nullable();
        $table->integer('orders_quantity')->nullable();
        $table->double('orders_money', 15, 2)->nullable();
    }
}