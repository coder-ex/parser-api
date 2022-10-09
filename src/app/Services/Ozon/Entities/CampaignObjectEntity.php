<?php

namespace App\Services\Ozon\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class CampaignObjectEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('object_campaign_id', 255)->index();

        $table->uuid('fk_campaign_id')->nullable();
        $table->foreign('fk_campaign_id')->references('id')->on("{$this->nameAPI}_{$this->name}_{$this->tplFK}")->onDelete('cascade');
    }
}
