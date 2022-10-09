<?php

namespace App\Services\WB\Processings;

use App\Services\Base\BaseProcessing;

class SaleProcessing extends BaseProcessing
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
            $value['status'] = $this->convertStatus($value);
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
        foreach ($data as $valData) {
            //--- все что свежее опорной даты в полученном массиве, пишем в новый массив
            if (strtotime($this->oldData[$field]) < strtotime($valData[$field])) {
                $valData['status'] = $this->convertStatus($valData);
                $this->newData[] = $valData;
            }
        }
    }

    /**
     * конвертирование statusId в Статус
     *
     * @param array $unit
     * @return string
     */
    protected function convertStatus(array $unit): string
    {
        if ($unit['saleID'][0] === 'S') {
            return "продажа";
        } elseif ($unit['saleID'][0] === 'R') {
            return "возврат";
        } elseif ($unit['saleID'][0] === 'D') {
            return "доплата";
        } elseif ($unit['saleID'][0] === 'A') {
            return "сторно";
        } elseif ($unit['saleID'][0] === 'B') {
            return "сторно возврата";
        }
    }
}
