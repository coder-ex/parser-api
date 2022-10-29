<?php

namespace App\Services\Base;

use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;

abstract class BaseProcessing
{
    /** фильтр при пустой БД */
    abstract protected function filterEmptyDB(array $data, string|null $project);
    /** фильтр при не пустой БД */
    abstract protected function filterNotEmptyDB(array $data, string|null $project, string|null $field);

    protected $debug;
    protected $newData = [];
    protected $oldData = [];

    /**
     * Undocumented function
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $task задача
     */
    public function __construct(
        protected string $table,
        protected string $typeDB,
        protected string $task,
    ) {
        $this->debug = env('APP_DEBUG');
    }

    public function __destruct()
    {
        unset($this->newData);
        unset($this->oldData);
    }

    /**
     * проверка пула данных
     *
     * @param array $data пул данных (массив элементов типа stdClass - object)
     * @param string $project идентификатор проекта
     * @return array
     */
    public function check(array $data, string|null $project): array
    {
        if (count($data) == 0) return [];
        if (count($data) == 1) {
            if (count($data[0]) == 0) return [];
        }

        $this->newData = [];
        $this->oldData = [];
        $field = null;

        $field = $this->classIsField($this->task);
        $this->oldData = (array)DB::connection($this->typeDB)->table($this->table)->where('project_id', $project)->orderBy($field, 'desc')->first();

        if (count($this->oldData) == 0) {                       // если oldData == null, значит таблица в БД пуста, поэтому вернем для записи весь новый массив
            $this->filterEmptyDB($data, $project);              // наполнение идет в $this->newData[]
        } else {                                                // если oldData != null, значит получим опорную дату - в старом массиве находим самое свеже время
            $this->filterNotEmptyDB($data, $project, $field);   // наполнение идет в $this->newData[]
        }

        //--- если $this->newData не пустой и задача не list
        $size = count($this->newData);
        if ($size > 0) {
            $this->quickSort($this->newData, $field);

            $dateEnd = '';
            if (count($this->oldData) > 0 && strtotime($this->oldData[$field]) > strtotime($this->newData[$size - 1][$field])) {
                $dateEnd = $this->oldData[$field];
            } else {
                $dateEnd = $this->newData[$size - 1][$field];
            }

            //--- запишем самую свежую дату в кэш для оптимизации метода, срок экспирации 30 суток
            cachePut($this->table, $project, $dateEnd, "{$this->task} : {$project}");
        }

        unset($data);

        //---
        return $this->newData;
    }

    public function classIsField(string $task): string|null
    {
        if ($task === 'reportDetailByPeriod') {                                                         // wildberies
            return 'rr_dt';
        } elseif ($task === 'incomes' || $task === 'orders' || $task === 'sales') {                     // wildberies
            return 'lastChangeDate';
        } elseif ($task === 'excise-goods' || $task === 'visit-list' || $task === 'statistics-daily') { // wildberies, roistat, ozon
            return 'date';
        } elseif ($task === 'list-orders' || $task === 'list-integration') {                            // roistat
            return 'creation_date';
        } elseif ($task === 'data') {                                                                   // roistat
            return 'dateTo';
        } elseif ($task === 'fbo-list') {                                                               // ozon
            return 'in_process_at';
        }
        //---
        return null;
    }

    private static function cmpDate($a, $b)
    {
        if (strtotime($a['date']) == strtotime($b['date'])) {
            return 0;
        }
        //---
        return (strtotime($a['date']) < strtotime($b['date'])) ? -1 : 1;
    }

    private static function cmpLCD($a, $b)
    {
        if (strtotime($a['lastChangeDate']) == strtotime($b['lastChangeDate'])) {
            return 0;
        }
        //---
        return (strtotime($a['lastChangeDate']) < strtotime($b['lastChangeDate'])) ? -1 : 1;
    }

    private static function cmpRRDT($a, $b)
    {
        if (strtotime($a['rr_dt']) == strtotime($b['rr_dt'])) {
            return 0;
        }
        //---
        return (strtotime($a['rr_dt']) < strtotime($b['rr_dt'])) ? -1 : 1;
    }

    private static function cmpListOrder($a, $b)
    {
        if (strtotime($a['creation_date']) == strtotime($b['creation_date'])) {
            return 0;
        }
        return (strtotime($a['creation_date']) < strtotime($b['creation_date'])) ? -1 : 1;
    }

    private static function cmpData($a, $b)
    {
        if (strtotime($a['dateTo']) == strtotime($b['dateTo'])) {
            return 0;
        }
        return (strtotime($a['dateTo']) < strtotime($b['dateTo'])) ? -1 : 1;
    }

    private static function cmpFBOL($a, $b)
    {
        if (strtotime($a['in_process_at']) == strtotime($b['in_process_at'])) {
            return 0;
        }
        return (strtotime($a['in_process_at']) < strtotime($b['in_process_at'])) ? -1 : 1;
    }

    protected function quickSort(array &$data, string $field): bool
    {
        if ($field === 'rr_dt') {                                           // wildberies
            return usort($data, [BaseProcessing::class, 'cmpRRDT']);
        } elseif ($field === 'lastChangeDate') {                            // wildberies
            return usort($data, [BaseProcessing::class, 'cmpLCD']);
        } elseif ($field === 'date') {                                      // wildberies, roistat, ozon
            return usort($data, [BaseProcessing::class, 'cmpDate']);
        } elseif ($field === 'creation_date') {                             // roistat
            return usort($data, [BaseProcessing::class, 'cmpListOrder']);
        } elseif ($field === 'dateTo') {                                    // roistat
            return usort($data, [BaseProcessing::class, 'cmpData']);
        } elseif ($field === 'in_process_at') {                             // ozon
            return usort($data, [BaseProcessing::class, 'cmpFBOL']);
        }
    }
}
