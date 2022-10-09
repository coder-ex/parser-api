<?php

namespace App\Services\Export\Entities;

use App\Services\Base\BaseEntity;
use Illuminate\Database\Schema\Blueprint;

class JournalEntity extends BaseEntity
{
    public function __construct(
        protected string $typeDB,   // тип БД определенной в .env
        string $tplTbl,             // шаблон таблицы
    ) {
        $this->entity = $tplTbl;
    }

    protected function addColumn(Blueprint $table)
    {
        $table->uuid('id')->primary();
        $table->dateTime('start_task')->index();    // дата/время старта в журнале
        $table->dateTime('stop_task')->index();     // дата/время остановки в журнале
        $table->string('task_flag', 25);            // флаг остановки START|STOP|ERROR
        $table->jsonb('description_flag');          // описание по флагу причины остановки ()
        $table->string('name_task')->index();       // название задачи
        $table->string('project_id')->index();      // id проекта
    }
}
