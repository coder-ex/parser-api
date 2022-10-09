<?php

namespace App\Services\Base;

use App\Repositories\Ozon\OzonRepository;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;

abstract class BaseService
{
    protected bool $debug;

    /**
     * конструктор
     *
     * @param [type] $repository
     */
    public function __construct()
    {
        $this->debug = env('APP_DEBUG');
    }

    /**
     * получение даты FROM
     * - алгоритм:
     *  1. проверяем кеш
     *  2. проверяем таблицу
     *  3. если в кеш есть, а в таблице нет, то пишем с исходной даты в планировщике
     *  4. если есть в кеш и в таблице, то пишем по данным в кеш
     *  5. если нет в кеш, а втаблице есть, то пишем по данным таблицы
     *  6. если нет в кеш и в таблице, то пишем с исходной даты в планировщике
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $project идентификатор проекта
     * @param string $from дата FROM из планировщика
     * @param string string $field поле сортировки при выборке из таблицы
     * @param string $timezoneId таймзона, по умолчанию == 'Europe/Moscow'
     * @return string
     */
    public function getDateFrom(string $table, string $typeDB,  string $project, string $from, string $field, string $timezoneId = 'Europe/Moscow'): string
    {
        date_default_timezone_set($timezoneId);                         // для коррекции тайм-зоны

        try {
            //--- вытащим последнюю дату обновления
            $cacheDateFrom = cacheGet($table, $project);

            // $field = $this->classIsField($this->task);
            $oldData = DB::connection($typeDB)->table($table)->where('project_id', $project)->orderBy($field, 'desc')->first();

            if (!is_null($cacheDateFrom) && is_null($oldData)) {            // п.3
                return date('Y-m-d H:i:s', strtotime($from));
            } elseif (!is_null($cacheDateFrom) && !is_null($oldData)) {     // п.4
                return date('Y-m-d H:i:s', strtotime($cacheDateFrom));
            } elseif (is_null($cacheDateFrom) && !is_null($oldData)) {      // п.5
                return date('Y-m-d H:i:s', strtotime($oldData->{$field}));
            } elseif (is_null($cacheDateFrom) && is_null($oldData)) {       // п.6
                return date('Y-m-d H:i:s', strtotime($from));
            }
        } catch (Exception | ErrorException $e) {
            throw $e;
        }

        //---
        return date('Y-m-d H:i:s', strtotime($from));                   // если данных нет в кеше и в таблицах, то получим с установленной даты для задачи в планировщике
    }

    /*
    public function createUrl(string $apiURL, string $task, ?string $secret = '', ?string $project = '', ?string $from = '', ?string $to = ''): string|null
    {
        if ($task === 'stock-warehouses') {
            return $apiURL . "/v3/product/info/stocks";
        } elseif ($task === 'fbo-list') {
            return $apiURL . "/v2/posting/fbo/list";
        } elseif ($task === 'ListOfGoodsID') {
            return $apiURL . "/v2/product/info/list";
        } elseif ($task === 'campaign') {
            return "{$apiURL}/api/client/campaign?client_secret={$secret}&client_id={$project}";
        } elseif ($task === 'statistics-media-compaign') {
            return "{$apiURL}/api/client/statistics/campaign/media?from={$from}&to={$to}";
        } elseif ($task === 'statistics-daily') {
            return "{$apiURL}/api/client/statistics/daily?client_secret={$secret}&client_id={$project}&dateFrom={$from}&dateTo={$to}";
        } elseif ($task === 'statistics-get-report') {
            return "{$apiURL}/api/client/statistics/report?UUID=";
        }
        //---
        return null;
    }
    */
}
