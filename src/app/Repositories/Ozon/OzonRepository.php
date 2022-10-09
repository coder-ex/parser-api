<?php

namespace App\Repositories\Ozon;

use App\Repositories\Base\Repository;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OzonRepository extends Repository
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * запись данных с возвратом дополненного массива id...
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param array $data массив данных
     * @return array
     */
    public function insertComby(string $table, string $typeDB, array $data)
    {
        $res = [];
        DB::connection($typeDB)->beginTransaction();
        try {
            foreach ($data as $key => $value) {
                //--- запомним в переменной и удалим в элементе перед втсавкой
                $products = $value['products'];
                unset($value['products']);

                $value['id'] = Str::uuid();
                DB::connection($typeDB)->table($table)->insert($value);
                
                //--- вернем в элемент для дальнейшей обработки в эндпоинт сервисе
                $res[$key] = $value;
                $res[$key]['products'] = $products;
            }

            DB::connection($typeDB)->commit();
        } catch (Exception | ErrorException $e) {
            DB::connection($typeDB)->rollback();
            unset($res);
            throw $e;
        }
        //---
        return $res;
    }
}
