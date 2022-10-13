<?php

namespace App\Repositories\WB;

use App\Repositories\Base\Repository;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

class WbRepository extends Repository
{
    /**
     * вставка или обновление
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param array $data массив данных
     * @return void
     */
    public function upsertTable(string $table, string $typeDB, array $data)
    {
        DB::connection($typeDB)->beginTransaction();
        try {
            DB::connection($typeDB)->table($table)->upsert(
                $data,
                ['odid', 'lastChangeDate'],
                //--- в данном случае обновим все поля
                // [
                //     'supplierArticle', 'techSize', 'barcode', 'totalPrice',
                //     'discountPercent', 'warehouseName', 'oblast', 'incomeID', 'nmId',
                //     'subject', 'category', 'brand', 'isCancel', 'cancel_dt', 'gNumber', 'sticker'
                // ]
            );

            DB::connection($typeDB)->commit();
        } catch (Exception | ErrorException $e) {
            DB::connection($typeDB)->rollback();
            throw $e;
        }
    }
}
