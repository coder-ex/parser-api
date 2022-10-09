<?php

namespace App\Services\Roistat\Processings;

use App\Services\Base\BaseProcessing;

class VisitListProcessing extends BaseProcessing
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
            // //--- исключим нулевые суммы
            // if ($value['marketing_cost'] === 0) {
            //     continue;
            // }

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
        //--- получим массив за опорную дату, что бы выбрать из этой даты то, чего нет в базе
        $range = $this->class::on($this->typeDB)->where('project_id', $project)
            ->where('date', '=', $this->oldData['date'])
            ->orderBy($field, 'desc')->get();

        //--- найдем в последней дате старого массива новые элементы
        $flag = false;
        foreach ($data as $valData) {
            if (!array_key_exists($field, $valData)) return;

            if ($flag) $flag = false;

            //--- все что свежее опорной даты в полученном массиве, пишем в новый массив
            if (strtotime($this->oldData[$field]) < strtotime($valData[$field])) {
                $this->newData[] = $valData;
                continue;
            }

            //--- проверим данные в опорной дате, что бы выбрать то, чего нет в БД
            foreach ($range as $valRange) {
                //--- исключим одинаковый элемент массива
                if ($this->compData($valData, $valRange,)) {
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
            (int)($a['project_id']) === $b['project_id'] && $a['visit_id'] === $b['visit_id'] &&
            $a['first_visit_id'] === $b['first_visit_id'] && $a['host'] === $b['host'] 
        ) {
            $date = ($b['date'] === null) ? $b['date'] : date("Y-m-d H:i:sO", strtotime($b['date']));

            if (strtotime($a['date']) === strtotime($date)) {
                return true;
            }
        }
        //---
        return false;
    }
}
