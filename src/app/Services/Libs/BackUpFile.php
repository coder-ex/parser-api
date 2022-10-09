<?php

namespace App\Services\Libs;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use ZipArchive;

class BackUpFile
{

    /**
     * упаковщик таблиц
     *  - ozon_aqua_stock_warehouses / fields == offer_id
     *  - ozon_aqua_fbo_lists / fields == created_at
     *  - ozon_aqua_products / fields == sku
     *
     * @return void
     */
    public function pack(string $table, string $field, ?string $date = null, ?string $typeDB = null)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', -1);

        if (!is_null($typeDB)) {
            $typeDB = $typeDB;
        } else {
            $typeDB = env('TYPE_DB');
        }

        $name = date('Y-m-d') . "-{$table}";    // имя: data + table
        $dataDB = [];
        try {
            $this->getData($dataDB, $table, $field);  // получим выборку из таблиц
            //$buff = serialize($dataDB);                     // сериализуем в byte массив
            $this->zipData($buff, $name);                   // упакуем в архив zip
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function unpack(string $table, ?string $date = null, ?string $typeDB = null)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', -1);

        if (!is_null($typeDB)) {
            $typeDB = $typeDB;
        } else {
            $typeDB = env('TYPE_DB');
        }

        $name = is_null($date) ? date('Y-m-d') . "-{$table}" : $date . "-{$table}";    // имя: data + table

        try {
            $zip = new ZipArchive();
            $res = $zip->open(storage_path("app/{$name}.zip"));
    
            if ($res === TRUE) {
                $zip->extractTo(storage_path("app/"));
                $zip->close();
            } else {
                throw new Exception('ошибка с кодом:' . $res);
            }
    
            $buff = File::get(
                storage_path("app/{$name}")
            );
    
            // предусмотреть удаление промежуточного файла, созданного при распаковке архива
    
            return unserialize($buff);
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * упаковка в архив zip
     *
     * @param string $buff - буффер с данными
     * @param string $name - жеш имени архива в формате: дата + имя таблицы -> hash
     * @return void
     */
    private function zipData(string $buff, string $name)
    {
        $zip = new ZipArchive();
        $path = storage_path("app/{$name}.zip");

        if ($zip->open($path, ZipArchive::CREATE) !== TRUE) {
            throw new Exception("Невозможно открыть <$path>\n");
        }

        $zip->addFromString($name, $buff);
        $zip->close();
    }

    /**
     * получение данных из таблиц
     *
     * @param array $buff буфер для получения массива с данными
     * @param string $table таблица
     * @param string $field поле для сортировки
     * @return void
     */
    private function getData(array &$buff, string $table, string $field)
    {
        $count = 0;
        DB::connection('mysql')->table($table)
            ->orderBy($field, 'desc')
            ->chunk(10000, function ($chunk) use (&$count, &$buff) {
                foreach ($chunk as $item) {
                    $count++;
                    /*
                    1. сериализуем элемент serialize()
                    2. получаем размер строки strlen()
                    3. создаем блок в памяти с идентификатором под размер строки shmop_open()
                    4. пишем в разделяемую память данные shmop_write()
                    5. формируем структуру на базе stdClass(): адрес блока, id блока, размер блока
                    6. пишем в буффер структуру как элемент
                    */
                    $unit = serialize($item);   // 1
                    $size = strlen($unit);      // 2


                    
                }
            });
    }
}
