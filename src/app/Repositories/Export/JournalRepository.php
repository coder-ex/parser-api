<?php

namespace App\Repositories\Export;

use App\Repositories\Base\Repository;
use Illuminate\Support\Str;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;

class JournalRepository extends Repository
{
    /**
     * обновление
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param array $item элемент данных
     * @return void
     */
    public function updateTable(string $table, string $typeDB, array $item)
    {
        DB::connection($typeDB)->beginTransaction();
        try {
            DB::connection($typeDB)->table($table)->where('id', $item['id'])->update($item);
            DB::connection($typeDB)->commit();
        } catch (Exception | ErrorException $e) {
            DB::connection($typeDB)->rollback();
            throw $e;
        }
    }

    /**
     * поиск task
     *
     * @param string $table
     * @param string $typeDB
     * @param array $item
     * @return object|null
     */
    public function isTask(string $table, string $typeDB, array $item)
    {
        return DB::connection($typeDB)->table($table)
            ->where('start_task', $item['start_task'])
            ->where('task_flag', $item['task_flag'])
            ->where('name_task', $item['name_task'])
            ->where('project_id', $item['project_id'])->first();
    }

    /**
     * удаление записи
     *
     * @param string $table
     * @param string $typeDB
     * @param string $id
     * @return void
     */
    public function deleteItem(string $table, string $typeDB, string $id)
    {
        DB::connection($typeDB)->beginTransaction();
        try {
            DB::connection($typeDB)->table($table)->where('id', $id)->delete();
            DB::connection($typeDB)->commit();
        } catch (Exception | ErrorException $e) {
            DB::connection($typeDB)->rollback();
            throw $e;
        }
    }
}
