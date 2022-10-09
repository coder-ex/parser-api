<?php

namespace App\Services\WB\Traits;

use Exception;
use Illuminate\Support\Facades\Http;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;

/** получение данных из АПИ */
trait FetchDataFromAPI
{
    protected int $attempts = 100;
    protected int $timeout = 5;

    /**
     * Undocumented function (413 == уменьшить число запросов до 1000 до 07.09.2022)
     *
     * @param string $url
     * @param string $dateTo
     * @return mixed
     */
    protected function fetchToAPIReport(string $url, string $dateTo): mixed
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $rrd_id = 0;
        $fullUrl = $url . '&rrdid=' . $rrd_id . '&dateTo=' . $dateTo;
        $cnt = 0;
        $flag = false;

        $count = 0;
        do {
            if ($flag) $flag = false;

            $options = ['connect_timeout' => 60, 'timeout' => 180];
            $response = Http::withOptions($options)->get($fullUrl);

            if ($response->status() >= 400) {
                if ($response->status() == 429) {       // error 429 слишком много запросов
                    if ($count > $this->attempts) {
                        $response->throw();
                    }

                    sleep(60);   // делаем паузу в 1 минуту
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос\n";

                    continue;
                } elseif ($response->status() == 500) {  // error 500 сервер не доступен
                    if ($count > $this->attempts) {
                        $response->throw();
                    }

                    sleep(120);   // делаем паузу в 2 минуты
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос\n";

                    continue;
                } else {                                // какая то неизвестная ошибка
                    $response->throw();
                }
            }

            $json = $response->json();

            if (is_null($json)) {
                sleep($this->timeout);
                break;
                // if ($count > $this->attempts) {
                //     $response->throw();
                // }

                // sleep($this->timeout);  // делаем паузу в 5 секунд
                // $count++;

                // echo "N. ", $count, " | вышли на повторный запрос\n";

                // continue;
            } elseif (count($json) > 0) {
                if ($count > 0) $count = 0;

                foreach ($json as $unit) {
                    yield $unit;
                }

                $rrd_id = $json[count($json) - 1]['rrd_id'];
                $fullUrl = $url . '&rrdid=' . $rrd_id . '&dateTo=' . $dateTo;

                if ($rrd_id == 0) {
                    break;
                }

                sleep($this->timeout);
            } else {
                yield [];
            }

            break;
        } while (true);
    }

    /**
     * общий запрос по API https://openapi.wildberries.ru/#tag/Statistika
     *
     * @param string $url url по API
     * @return Generic
     */
    protected function fetchToAPI(string $url)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $count = 0;
        do {
            $options = ['connect_timeout' => 60, 'timeout' => 180];
            $response = Http::withOptions($options)->get($url);

            if ($response->status() >= 400) {
                if ($response->status() == 429) {       // error 429 слишком много запросов
                    if ($count > $this->attempts) {
                        $response->throw();
                    }

                    sleep(60);   // делаем паузу в 1 минуту
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос\n";

                    continue;
                } elseif ($response->status() == 500) {  // error 500 сервер не доступен
                    if ($count > $this->attempts) {
                        $response->throw();
                    }

                    sleep(120);   // делаем паузу в 2 минуты
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос\n";

                    continue;
                } else {                                // какая то неизвестная ошибка
                    $response->throw();
                }
            }

            $json = $response->json();

            if (is_null($json)) {
                if ($count > $this->attempts) {
                    $response->throw();
                }

                sleep(5);   // делаем паузу в 5 секунд
                $count++;

                echo "N. ", $count, " | вышли на повторный запрос\n";

                continue;
            } elseif (count($json) > 0) {
                if ($count > 0) $count = 0;

                foreach ($json as $unit) {
                    yield $unit;
                }
            } else {
                yield [];
            }

            break;
        } while (true);
    }

    // /**
    //  * создание url по API
    //  *
    //  * @param string $table таблица выгрузки
    //  * @param string $urlAPI шаблон url от API WB
    //  * @param string $secret токен от магазина WB
    //  * @param string $task задача
    //  * @param string $project идентификатор проекта
    //  * @param string $dateFrom с какой даты забираем данные, ссылкой для изменения вне области видимости
    //  * @param integer $flag флаг, если == 0 то забираем данные в диапазоне, если == 1 то только по dateFrom
    //  * @param integer $limit
    //  * @return string
    //  */
    // protected function createUrl(string $table, string $apiURL, string $secret, string $task, string $project, string $dateFrom, int $flag = 0, int $limit = 0): string
    // {
    //     $cacheDateFrom = cacheGet($table, $project);

    //     if (!is_null($cacheDateFrom)) {     // если данные в кэше есть, то получим с даты из кэша
    //         $dateFrom = $cacheDateFrom;     // ... иначе, получим с установленной даты для задачи в планировщике
    //     }

    //     if ($task === 'incomes' || $task === 'stocks') {
    //         return $apiURL . '/' . $task . '?dateFrom=' . $dateFrom . '&key=' . $secret;
    //     } elseif ($task === 'reportDetailByPeriod') {
    //         return $apiURL . '/' . $task . '?dateFrom=' . $dateFrom . '&key=' . $secret . '&limit=' . $limit;
    //     } elseif ($task === 'excise-goods') {
    //         return $apiURL . '/' . $task . '?key=' . $secret . '&dateFrom=' . $dateFrom;
    //     }
    //     //---
    //     return $apiURL . '/' . $task . '?dateFrom=' . $dateFrom . '&flag=' . $flag . '&key=' . $secret;
    // }

    /**
     * Undocumented function (413 == уменьшить число запросов до 1000 до 07.09.2022)
     *
     * @param string $task
     * @param string $url
     * @param string $dateTo
     * @return mixed
     */
    protected function fetchToAPIReport_OLD(string $task, string $url, string $dateTo): mixed
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $rrd_id = 0;
        $fullUrl = $url . '&rrdid=' . $rrd_id . '&dateTo=' . $dateTo;
        $cnt = 0;
        $flag = false;
        do {
            if ($flag) $flag = false;

            try {
                $options = ['connect_timeout' => 60, 'timeout' => 180];
                $result = Http::withOptions($options)->get($fullUrl)->throw()?->json();

                if (is_null($result)) {
                    break;
                }

                foreach ($result as $unit) {
                    yield $unit;
                }

                $rrd_id = $result[count($result) - 1]['rrd_id'];
                $fullUrl = $url . '&rrdid=' . $rrd_id . '&dateTo=' . $dateTo;

                if ($rrd_id == 0) {
                    break;
                }

                sleep($this->timeout);

                if ($cnt > 0) $cnt = 0;
            } catch (Exception $e) {
                if ($e->getCode() == 0) {

                    echo "N. ", $cnt, " | вышли на повторный запрос\n";

                    sleep($this->timeout);
                    $flag = true;
                } elseif ($e->getCode() >= 400) {
                    //outMsg($task, 'error.', $e->getMessage(), $this->debug);
                    throw $e;
                } else {
                    break;
                }
            }

            if ($flag) $cnt++;

            if ($cnt > 5) break;
        } while (true);
    }
}
