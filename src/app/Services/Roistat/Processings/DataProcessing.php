<?php

namespace App\Services\Roistat\Processings;

use App\Helpers\TypeTask;
use App\Repositories\Export\ServiceRepository;
use App\Repositories\Roistat\RoistatRepository;
use App\Services\Base\BaseProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DataProcessing extends BaseProcessing
{
    /**
     * фильтр при пустой БД
     *
     * @param array $data массив с новыми данными
     * @return void
     */
    protected function filterEmptyDB(array $data, string|null $project)
    {
        $service = new ServiceRepository();
        $task = $service->getTaskOne($this->typeDB, $project, TypeTask::Data->value);

        foreach ($data as $value) {
            //--- исключим нулевые суммы
            if ($value['marketing_cost'] === 0) {
                continue;
            }

            $this->markerBinding($value, $task->name);    // свяжем marker
            $value['id'] = DB::connection($this->typeDB)->table($this->table)->where('project_id', $project)->where('marker_id', $value['marker_id'])->where('dateFrom', $value['dateFrom'])->first()?->id;
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
        $service = new ServiceRepository();
        $task = $service->getTaskOne($this->typeDB, $project, TypeTask::Data->value);

        //--- получим массив за опорную дату, что бы выбрать из этой даты то, чего нет в базе
        $range = DB::connection($this->typeDB)->table($this->table)
            ->where('project_id', $project)
            ->where('dateFrom', '=', $this->oldData['dateFrom'])
            ->orderBy($field, 'desc')->get();

        //--- найдем в последней дате старого массива новые элементы
        $flag = false;
        foreach ($data as $valData) {
            if ($flag) $flag = false;

            //--- исключим нулевые суммы
            if ($valData['marketing_cost'] === 0) {
                continue;
            }

            //--- все что свежее опорной даты в полученном массиве, пишем в новый массив
            if (strtotime($this->oldData[$field]) < strtotime($valData[$field])) {
                $this->markerBinding($valData, $task->name);    // свяжем marker
                $valData['id'] = null;
                $this->newData[] = $valData;
                continue;
            }

            //--- дозагрузка новых данных не требуется
            $this->markerBinding($valData, $task->name);    // свяжем marker
            $valData['id'] = DB::connection($this->typeDB)->table($this->table)->where('project_id', $project)->where('marker_id', $valData['marker_id'])->where('dateFrom', $valData['dateFrom'])->first()?->id;
            $this->newData[] = $valData;


            // //--- проверим данные в опорной дате, что бы выбрать то, чего нет в БД
            // foreach ($range as $valRange) {
            //     //--- исключим одинаковый элемент массива
            //     if ($this->compData($valData, $valRange,)) {
            //         continue;
            //     } else {
            //         $flag = true;
            //         break;
            //     }
            // }

            // if ($flag) {
            //     //$valData['source_id'] = $this->sourceBinding($valData);  // свяжем source
            //     $this->markerBinding($valData, $task->name);    // свяжем marker
            //     $this->newData[] = $valData;
            // }
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
            $a['project_id'] === $b['project_id'] &&
            strtotime($a['dateFrom']) === strtotime($b['dateFrom']) &&
            strtotime($a['dateTo']) === strtotime($b['dateTo']) // &&
        ) {
            return true;
        }
        //---
        return false;
    }

    /**
     * создание и связывание source_xxx
     *
     * @param array &$item элемент нового массива
     * @return string|null
     */
    private function markerBinding(array &$item, string $nameTask)
    {
        // проверим в каталоге есть ли marker_level_3lev
        // ... 1. если нет то берем marker_level_N_title -> channel_N (до 3-х уровней)
        // ... заносим marker_level_3lev -> в marker
        // ... записываем и связываем
        // ... 2. если есть, то просто связываем
        // первым грузим data

        $entity = "roistat_{$nameTask}_catalog_markers";

        $value = [];
        if (!empty($item['marker_level_1_value'])) $value[] = $item['marker_level_1_value'];
        if (!empty($item['marker_level_2_value'])) $value[] = $item['marker_level_2_value'];
        if (!empty($item['marker_level_3_value'])) $value[] = $item['marker_level_3_value'];

        $markerLev_3lev = implode('_', $value);

        $title = [];
        if (!empty($item['marker_level_1_title'])) $title[] = $item['marker_level_1_title'];
        if (!empty($item['marker_level_2_title'])) $title[] = $item['marker_level_2_title'];
        if (!empty($item['marker_level_3_title'])) $title[] = $item['marker_level_3_title'];

        $item['marker_id'] = $this->addChannels($entity, $title, $markerLev_3lev);

        //--- удалим лишние поля
        unset($item['marker_level_1_title']);
        unset($item['marker_level_1_value']);
        unset($item['marker_level_2_title']);
        unset($item['marker_level_2_value']);
        unset($item['marker_level_3_title']);
        unset($item['marker_level_3_value']);
    }

    private function addChannels(string $entity, array $channel, ?string $uniqName_3lev)
    {
        $hash = md5(is_null($uniqName_3lev) ? ' ' : $uniqName_3lev);

        $catalog = new RoistatRepository();
        $result = $catalog->isHash($this->typeDB, $entity, $hash);

        //--- если значения hash нет в справочнике, значит добавим в справочник
        if (is_null($result)) {
            $catalog->insertQuery(
                $this->typeDB,
                "insert into {$entity} (id, marker, channel_1, channel_2, channel_3, hash) values (?,?,?,?,?,?)",
                [
                    [
                        Str::uuid(), //->toString(),
                        $uniqName_3lev,
                        $channel[0] ?? null,
                        $channel[1] ?? null,
                        $channel[2] ?? null,
                        $hash
                    ],
                ]
            );
        } else {
            return $result->id;
        }
        //---
        return $catalog->isHash($this->typeDB, $entity, $hash)->id;
    }
}
