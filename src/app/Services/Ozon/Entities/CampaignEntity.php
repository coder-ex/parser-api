<?php

namespace App\Services\Ozon\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class CampaignEntity extends BaseEntity
{
    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->string('project_id')->index();
        $table->date('published_at')->nullable();       // дата получения среза
        $table->string('campaign_id')->index();         // нужно будет сделать индексируемое поле
        $table->string('title')->index();               // индексируемое поле
        $table->string('state')->nullable();
        $table->string('advObjectType')->nullable();
        $table->string('fromDate')->nullable();
        $table->string('toDate')->nullable();
        $table->double('dailyBudget', 15, 2)->nullable();
        $table->jsonb('placement')->nullable();
        $table->double('budget', 15, 2)->nullable();
        $table->datetime('createdAt')->nullable();      // timestamp не корректно работает, 2022-09-13T08:04:44.000Z в MySql не воспринимает (Postgres принимает нормально)
        $table->datetime('updatedAt')->nullable();      // timestamp не корректно работает, 2022-09-13T08:04:44.000Z в MySql не воспринимает (Postgres принимает нормально)
        $table->string('productCampaignMode')->nullable();
        $table->string('productAutopilotStrategy')->nullable();
        $table->jsonb('autopilot')->nullable();
    }
}