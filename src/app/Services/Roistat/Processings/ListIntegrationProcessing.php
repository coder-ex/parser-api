<?php

namespace App\Services\Roistat\Processings;

use App\Helpers\TypeTask;
use App\Repositories\Export\ServiceRepository;
use App\Repositories\Roistat\RoistatRepository;
use App\Services\Base\BaseProcessing;
use App\Services\Roistat\Traits\FetchDataFromAPI;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ListIntegrationProcessing extends BaseProcessing
{
    use FetchDataFromAPI;

    /**
     * фильтр при пустой БД
     *
     * @param array $data массив с новыми данными
     * @return void
     */
    protected function filterEmptyDB(array $data, string|null $project)
    {
        $service = new ServiceRepository();
        $task = $service->getTaskOne($this->typeDB, $project, TypeTask::ListIntegration->value);

        foreach ($data as $value) {
            $this->markerBinding($value, $task->name);    // свяжем marker
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
        $task = $service->getTaskOne($this->typeDB, $project, $this->task);

        //--- получим массив за опорную дату, что бы выбрать из этой даты то, чего нет в базе
        $range = DB::connection($this->typeDB)->table($this->table)
            ->where('project_id', $project)
            ->where('creation_date', '=', $this->oldData[$field])
            ->orderBy($field, 'desc')->get();

        //--- найдем в последней дате старого массива новые элементы
        $flag = false;
        foreach ($data as $valData) {
            if (!array_key_exists($field, $valData)) return;

            if ($flag) $flag = false;

            //--- все что свежее опорной даты в полученном массиве, пишем в новый массив
            if (strtotime($this->oldData[$field]) < strtotime($valData[$field])) {
                $this->markerBinding($valData, $task->name);
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
                $this->markerBinding($valData, $task->name);
                $this->newData[] = $valData;
            }
        }
    }

    /**
     * сравнение элемента в массиве (order_id является уникальным)
     *
     * @param [type] $a элемент в новом массиве
     * @param [type] $b элемент в БД массиве
     * @return boolean true если элементы равны, иначе false
     */
    private function compData($a, $b): bool
    {
        if ($a['project_id'] === $b['project_id'] && $a['order_id'] === $b['order_id']) {
            return true;
        }
        //---
        return false;
    }

    /**
     * связывание маркера с каталогом маркеров
     *
     * @param array $item
     * @return void
     */
    private function markerBinding(array &$item, string $nameTask)
    {
        // разбиваем поля по делиметру '_'
        $roistat = $this->splitByDelimiter($item['roistat']);
        $dispName = $this->splitByDelimiter($item['display_name']);
        $sysName = $this->splitByDelimiter($item['system_name']);

        //--- создаем xxx_3lev
        $roistat_3lev = is_null($roistat) ? null : $this->create3LevSource($roistat);
        $dispName_3lev = is_null($dispName) ? null : $this->create3LevSource($dispName);
        $sysName_3lev = is_null($sysName) ? null : $this->create3LevSource($sysName);

        //--- проверяем roistat на число или нет
        if (!empty($roistat[0]) && is_numeric($roistat[0])) {   // если $roistat is number
            if (!empty($sysName_3lev)) {                        // если sysName_3lev != null
                $item['marker'] = $sysName_3lev;
                $item['marker_id'] = $this->addChannels("roistat_{$nameTask}_catalog_markers", $dispName, $sysName_3lev, $dispName_3lev);
            }
        } else {                                                // если roistat is not number
            $item['marker'] = $roistat_3lev;
            $item['marker_id'] = $this->addChannels("roistat_{$nameTask}_catalog_markers", $roistat, $roistat_3lev, $dispName_3lev);
        }
    }

    private function addChannels(string $entity, array $channel, ?string $uniqName_3lev, ?string $dispName_3lev)
    {
        $hash = md5(is_null($uniqName_3lev) ? ' ' : $uniqName_3lev);

        $repository = new RoistatRepository();
        $result = $repository->isHash($this->typeDB, $entity, $hash);

        //--- если значения hash нет в справочнике, значит добавим в справочник
        if (is_null($result)) {
            $repository->insertQuery(
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
        return $repository->isHash($this->typeDB, $entity, $hash)->id;
    }

    /**
     * создание xxx_3lev
     *
     * @param array $array
     * @return string|null
     */
    private function create3LevSource(array $array): string|null
    {
        $sep = '_';
        $xxx_3lev = null;
        $length = count($array);
        for ($i = 0; $i < $length; $i++) {
            //if ($i == 2) break; // ограничим 3 уровнем

            if ($i == 0) {
                $xxx_3lev = $array[$i];
                continue;
            }

            $xxx_3lev = $xxx_3lev . $sep . $array[$i];

            if ($i == 2) break; // ограничим 3 уровнем
        }
        //---
        return $xxx_3lev;
    }

    /**
     * разбить по делиметру
     *
     * @param string $value строка
     * @param string $separator разделитель
     * @return array|null возвращает массив либо null, если строка пустая
     */
    private function splitByDelimiter(string|null $value, string $separator = "_"): array|null
    {
        if (empty($value)) return null;

        return explode($separator, $value);
    }

    /**
     * распределение заявок по каналам
     *
     * @param array $item
     * @return void
     */
    private function DistribOfAppByChannel_OLD(array &$item)
    {
        // разбиваем поля по делиметру '_'
        $roistat = $this->splitByDelimiter($item['roistat']);
        $dispName = $this->splitByDelimiter($item['display_name']);
        $sysName = $this->splitByDelimiter($item['system_name']);

        //--- проверяем roistat на число или нет
        if (!empty($roistat[0]) & is_numeric($item['roistat'])) {    // если число
            if (!empty($dispName[0])) {                             // если значение display_name[0] есть
                $item['channel_1'] = $dispName[0] ?? null;
                $item['channel_2'] = $dispName[1] ?? null;
                $item['channel_3'] = $dispName[2] ?? null;
            } elseif (!empty($sysName[0])) {                         // если значение system_name[0] есть
                $item['channel_1'] = $sysName[0] ?? null;
                $item['channel_2'] = $sysName[1] ?? null;
                $item['channel_3'] = $sysName[2] ?? null;
            } elseif (empty($sysName[0])) {                         // если значения system_name[0] нет
                $item['channel_1'] = $roistat[0] ?? null;
                $item['channel_2'] = $roistat[1] ?? null;
                $item['channel_3'] = $roistat[2] ?? null;
            }
        } else {                                                    // если не число
            if (!empty($sysName[0])) {                               // если значение system_name[0] есть
                if (!empty($dispName[0])) {                         // если значение display_name[0] есть
                    $item['channel_1'] = $dispName[0] ?? null;
                    $item['channel_2'] = $dispName[1] ?? null;
                    $item['channel_3'] = $dispName[2] ?? null;
                } else {                                            // если значения display_name[0] нет
                    $item['channel_1'] = $sysName[0] ?? null;
                    $item['channel_2'] = $sysName[1] ?? null;
                    $item['channel_3'] = $sysName[2] ?? null;
                }
            } else {                                                // если значения system_name[0] нет
                $item['channel_1'] = $roistat[0] ?? null;
                $item['channel_2'] = $roistat[1] ?? null;
                $item['channel_3'] = $roistat[2] ?? null;
            }
        }
    }

    /**
     * проверяет ли в таблице visit установленный id визита
     *
     * @param string $table имя таблицы в которой ищзем
     * @param string $project имя (id) проекта 
     * @param string $roistat id визита из поля roistat
     * @return array|null
     */
    private function isVisit(string $table, string $project, string $roistat): array|null
    {
        $visit = DB::connection($this->typeDB)->table($table)
            ->where('project_id', '=', $project)
            ->where('visit_id', $roistat)
            ->first();

        if (is_null($visit)) {
            return null;
        }

        return [
            'source_system_name' => $visit->source_system_name,
            'source_display_name' => $visit->source_display_name,
        ];
    }
}
