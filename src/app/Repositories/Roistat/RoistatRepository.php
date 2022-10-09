<?php

namespace App\Repositories\Roistat;

use App\Repositories\Base\Repository;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

class RoistatRepository extends Repository
{
    public function isHash(string $typeDB, string $entity, string $hash)
    {
        try {
            return DB::connection($typeDB)->table($entity)
                ->where('hash', '=', $hash)
                ->first();
        } catch (Exception | ErrorException $e) {
            throw $e;
        }
    }

    /**
     * обновление
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param array|object $data массив данных
     * @return void
     */
    public function updateTable(string $table, string $typeDB, array|object $data)
    {
        DB::connection($typeDB)->beginTransaction();
        try {
            foreach ($data as $value) {
                $valArray = ($value instanceof stdClass) ? (array)$value : $value;
                //DB::connection($typeDB)->table($table)->upsert($valArray, ['id'], ['status_name', 'fields_in_prior']);

                DB::connection($typeDB)->table($table)
                    ->where('id', $valArray['id'])
                    ->update(
                        [
                            'status_name' => $valArray['status_name'],
                            'fields_manager'=>$valArray['fields_manager'],
                            'fields_in_prior' => $valArray['fields_in_prior'],
                            'fields_work_prior'=>$valArray['fields_work_prior'],
                            'fields_target_lead'=>$valArray['fields_target_lead'],
                        ]
                    );
            }

            DB::connection($typeDB)->commit();
        } catch (Exception | ErrorException $e) {
            DB::connection($typeDB)->rollback();
            throw $e;
        }
    }

    /**
     * вставка или обновление
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param array|object $data массив данных
     * @return void
     */
    public function upsertTable(string $table, string $typeDB, array|object $data)
    {
        DB::connection($typeDB)->beginTransaction();
        try {
            foreach ($data as $value) {
                if (!is_null($value['id'])) {   // если id есть значит обновляем ...
                    DB::connection($typeDB)->table($table)
                        ->where('id', $value['id'])
                        ->update([
                            'marketing_cost' => $value['marketing_cost']
                        ]);
                } else {                        // ... иначе добавляем
                    $value['id'] = Str::uuid();
                    DB::connection($typeDB)->table($table)->insert($value);
                }
            }

            DB::connection($typeDB)->commit();
        } catch (Exception | ErrorException $e) {
            DB::connection($typeDB)->rollback();
            throw $e;
        }
    }
}
