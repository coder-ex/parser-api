<?php

namespace App\Services\Ozon\Processings;

use App\Services\Base\BaseProcessing;
use App\Repositories\Base\Repository;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FboListProcessing extends BaseProcessing
{
    /**
     * фильтр при пустой БД
     *
     * @param array $data массив с новыми данными
     * @param string|null $project идентификатор (имя) проекта
     * @return void
     */
    protected function filterEmptyDB(array $data, string|null $project)
    {
        foreach ($data as $value) {
            $this->newData[] = $value;
        }
    }

    /**
     * фильтр при не пустой БД
     *
     * @param array $data массив с новыми данными
     * @param string|null $project идентификатор (имя) проекта
     * @param string|null $field поле для фильтрации
     * @return void
     */
    protected function filterNotEmptyDB(array $data, string|null $project, string|null $field)
    {
        //--- получим массив за опорную дату, что бы выбрать из этой даты то, чего нет в базе
        $range = DB::connection($this->typeDB)->table($this->table)
            ->where('project_id', $project)
            ->where($field, '=', $this->oldData[$field])
            ->orderBy($field, 'desc')->get();

        //--- найдем в последней дате старого массива новые элементы
        $flag = false;
        foreach ($data as $valData) {
            if ($flag) $flag = false;

            //--- все что свежее опорной даты в полученном массиве, пишем в новый массив
            if (strtotime($this->oldData[$field]) < strtotime($valData[$field])) {
                $this->newData[] = $valData;
                continue;
            }

            //--- проверим данные в опорной дате, что бы выбрать то, чего нет в БД
            foreach ($range as $valRange) {
                //--- исключим одинаковый элемент массива
                if ($this->compData($valData, (array)$valRange)) {
                    continue;
                } else {
                    $flag = true;
                    break;
                }
            }

            if ($flag) {
                $this->newData[] = $valData;
            }
        }
    }

    /**
     * сравнение элемента в массиве
     *
     * @param [type] $a элемент в первом массиве
     * @param [type] $b элемент во втором массиве
     * @return boolean true если элементы равны, иначе false
     */
    private function compData($a, $b): bool
    {
        if (
            (int)($a['project_id']) === $b['project_id'] &&
            $a['posting_number'] === $b['posting_number']
        ) {
            return true;
        }
        //---
        return false;
    }

    /**
     * создание и связывание products
     *
     * @param string $table связанная таблица выгрузки
     * @param array &$item элемент нового массива
     * @return void
     */
    public function binding(string $table, array &$item)
    {
        if (is_null($item['products'])) return null;

        try {
            $products = json_decode($item['products']);
            foreach ($products as $unit) {
                $repository = new Repository();
                $repository->insertTable(
                    $table,
                    $this->typeDB,
                    [
                        [
                            'id' => Str::uuid(),
                            'sku' => $unit->sku,
                            'name' => $unit->name,
                            'quantity' => $unit->quantity,
                            'offer_id' => $unit->offer_id,
                            'price' => $unit->price,
                            'digital_codes' => json_encode($unit->digital_codes, JSON_UNESCAPED_UNICODE),
                            'fk_product_id' => $item['id'],
                        ]
                    ]
                );
            }
        } catch (Exception | ErrorException $e) {
            throw $e;
        }
    }
}
