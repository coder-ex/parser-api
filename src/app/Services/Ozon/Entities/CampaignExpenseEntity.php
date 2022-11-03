<?php

namespace App\Services\Ozon\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class CampaignExpenseEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id');
        $table->string('campaign_id')->index();
        $table->date('date')->nullable();
        $table->string('title')->nullable();
        $table->double('cost', 15, 2)->default(0.00);
        $table->double('costBonus', 15, 2)->default(0.00);
    }
}


