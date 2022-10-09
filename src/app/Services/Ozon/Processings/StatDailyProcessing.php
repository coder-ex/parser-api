<?php

namespace App\Services\Ozon\Processings;

use App\Services\Base\BaseProcessing;
use Illuminate\Support\Facades\DB;

class StatDailyProcessing extends BaseProcessing
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
        //--- удалим все данные за крайнее число, т.к. они не полные
        DB::connection($this->typeDB)->table($this->table)
            ->where('project_id', $project)
            ->where('date', $this->oldData[$field])
            ->delete();

        //--- найдем в последней дате старого массива новые элементы
        $flag = false;
        foreach ($data as $valData) {
            if ($flag) $flag = false;

            //--- все что свежее опорной даты в полученном массиве, пишем в новый массив
            if (strtotime($this->oldData[$field]) < strtotime($valData[$field])) {
                $this->newData[] = $valData;
                continue;
            }

            //--- исключим одинаковый элемент массива, поиск в БД по ключевым полям
            if ($this->isItemTable($project, $field, $valData)) {
                continue;
            } else {
                $flag = true;
            }

            if ($flag) {
                $this->newData[] = $valData;
            }
        }
    }

    /**
     * проверка элемента в таблице
     *
     * @param string $project id проекта
     * @param string $field поле фильтра
     * @param array $unit элемент в массиве данных
     * @return boolean
     */
    private function isItemTable(string $project, string $field, array $unit): bool
    {
        if (
            !is_null(DB::connection($this->typeDB)->table($this->table)
                ->where('project_id', $project)
                ->where('campaign_id', $unit['campaign_id'])
                ->where('date', $unit['date'])
                ->orderBy($field, 'desc')->first())
        ) {
            return true;
        }
        //---
        return false;
    }
}
