<?php

namespace App\Services\WB\Processings;

use App\Services\Base\BaseProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;

class IncomeProcessing extends BaseProcessing
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
        foreach ($data as $valData) {
            //--- все что свежее опорной даты в полученном массиве, пишем в новый массив
            // if (strtotime($this->oldData[$field]) < strtotime($valData[$field])) {
            //     $this->newData[] = $valData;
            // }

            //--- если находим одинаковый то пишем его в newData[]
            $ob = $this->dbFetch($project, $field, $valData);
            if (!is_null($ob)) {
                $valData['id'] = $ob->id;
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
     * @return stdClass|null
     */
    private function dbFetch(string $project, string $field, array $unit): stdClass|null
    {
        return DB::connection($this->typeDB)->table($this->table)
            ->where('project_id', $project)
            ->where('incomeId', $unit['incomeId'])
            ->where('supplierArticle', $unit['supplierArticle'])
            ->where('techSize', $unit['techSize'])
            ->orderBy($field, 'desc')->first();
    }
}
