<?php

namespace App\Services\Ozon\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class CampaignFoodEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id');
        $table->dateTime('published_at');
        $table->string('campaign_id')->index();
        $table->string('title')->nullable();
        $table->string('status')->nullable();
        $table->double('dailyBudget', 15, 2)->default(0.00);
        $table->double('cost', 15, 2)->default(0.00);
        $table->integer('showing')->default(0);
        $table->integer('clicks')->default(0);
        $table->double('averRate', 15, 2)->default(0.00);
        $table->double('averPrice', 15, 2)->default(0.00);
        $table->double('CTR', 15, 2)->default(0.00);
        $table->double('averCPC', 15, 2)->default(0.00);
        $table->integer('orderPiece')->default(0);
        $table->double('orderRub', 15, 2)->default(0.00);
        $table->double('PAE', 15, 2)->default(0.00);
    }
}
