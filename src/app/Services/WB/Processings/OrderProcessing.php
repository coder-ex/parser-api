<?php

namespace App\Services\WB\Processings;

use App\Services\Base\BaseProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

class OrderProcessing extends BaseProcessing
{
    /**
     * фильтр при пустой БД
     *
     * @param array $data массив с новыми данными
     * @return void
     */
    protected function filterEmptyDB(array $data, string|null $project)
    {
        foreach ($data as $value) {
            $value['id'] = Str::uuid();
            $this->newData[] = $value;
        }
    }

    /**
     * фильтр при не пустой БД 
     *
     * @param array $data массив с новыми данными
     * @param string $field
     * @return void
     */
    protected function filterNotEmptyDB(array $data, string|null $project, string|null $field)
    {
        $count = 0;
        foreach ($data as $valData) {
            // //--- все что свежее опорной даты в полученном массиве, пишем в новый массив
            // if (strtotime($this->oldData[$field]) < strtotime($valData[$field])) {
            //     $this->newData[] = $valData;
            // }

            //--- для теста
            if ($this->debug) {
                if (($count % 1000) == 0) {
                    echo "[ OrderProcessing::filterNotEmptyDB ]: N. ", $count, " | идем дальше\n";
                }

                $count++;
            }

            //--- если находим одинаковый то пишем его в newData[]
            $id = $this->dbFetch($project, $field, $valData);
            if (!is_null($id)) {
                $valData['id'] = $id;
                $this->newData[] = $valData;
                continue;
            }

            $valData['id'] = Str::uuid();
            $this->newData[] = $valData;
        }
    }

    /**
     * проверка элемента в таблице
     *
     * @param string $project id проекта
     * @param string $field поле фильтра
     * @param array $unit элемент в массиве данных
     * @return string|null
     */
    private function dbFetch(string $project, string $field, array $unit): string|null
    {
        return DB::connection($this->typeDB)->table($this->table)
            ->where('project_id', $project)
            ->where('date', $unit['date'])
            ->where('odid', $unit['odid'])
            ->orderBy($field, 'desc')->first()?->id;
    }

    // /**
    //  * сравнение
    //  *
    //  * @param stdClass $a
    //  * @param array $b
    //  * @return boolean
    //  */
    // private function compare_OLD(stdClass $a, array $b): bool
    // {
    //     $hash_a = $this->convToHash($a);
    //     $hash_b = $this->convToHash($b);

    //     if ($hash_a === $hash_b) {
    //         return true;
    //     }
    //     //---
    //     return false;
    // }

    // /**
    //  * конвертация в hash
    //  *
    //  * @param stdClass|array $value
    //  * @return void
    //  */
    // private function convToHash(stdClass|array $value)
    // {
    //     $res = ($value instanceof stdClass) ? (array)$value : $value;

    //     return md5($res['project_id'] . (string)strtotime($res['date']) . (string)strtotime($res['lastChangeDate']) . (string)$res['supplierArticle'] .
    //         (string)$res['techSize'] . (string)$res['barcode'] . (string)$res['totalPrice'] . (string)$res['discountPercent'] . (string)$res['warehouseName'] .
    //         (string)$res['oblast'] . (string)$res['incomeID'] . (string)$res['odid'] . (string)$res['nmId'] . (string)$res['subject'] . (string)$res['category'] .
    //         (string)$res['brand'] . (string)$res['isCancel'] . (string)$res['cancel_dt'] . (string)$res['gNumber'] . (string)$res['sticker']);
    // }
}
