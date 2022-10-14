<?php

namespace App\Services\Ozon\Traits;

use App\Services\Exceptions\AuthException;
use App\Services\Exceptions\Http404Exception;
use Illuminate\Support\Facades\Http;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;

/** получение данных из АПИ */
trait FetchDataFromAPI
{
    protected int $attempts = 100;

    /**
     * Undocumented function
     *
     * @param string $url полный url запроса
     * @param string $body тело запроса
     * @param array $header заголовок запроса
     * @return string
     */
    protected function fetchToAPIOzonOne(string $url, string $body, array $header)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $count = 0;
        do {
            $options = ['connect_timeout' => 30, 'timeout' => 120];
            $response = Http::withOptions($options)->withHeaders($header)->withBody($body, 'application/json')->post($url);

            if ($response->status() >= 400) {
                if ($response->status() == 403) {
                    throw new AuthException('The token is rotten', 403);
                } elseif ($response->status() == 404) {
                    throw new Http404Exception('Not Found', 404);
                } elseif ($response->status() == 429 || $response->status() == 500) {     // error 429 слишком много запросов / error 500 сервер не доступен
                    if ($count > $this->attempts) {
                        $response->throw();
                    }

                    sleep(5);   // делаем паузу в 5 сек для тестироания
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос 429 | 500 \n";

                    continue;
                } else {                                // какая то неизвестная ошибка
                    $response->throw();
                }
            }

            return $response->body();

            break;
        } while (true);
    }

    protected function fetchToAPIFile(string $url, array $header)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $count = 0;
        do {
            $options = ['connect_timeout' => 60, 'timeout' => 180];
            $response = Http::withOptions($options)->withHeaders($header)->get($url);

            if ($response->status() >= 400) {
                if ($response->status() == 403) {
                    throw new AuthException('The token is rotten', 403);
                } elseif ($response->status() == 404) {
                    throw new Http404Exception('Not Found', 404);
                } elseif ($response->status() == 429 || $response->status() == 500) {     // error 429 слишком много запросов / error 500 сервер не доступен
                    if ($count > $this->attempts) {
                        $response->throw();
                    }

                    sleep(5);   // делаем паузу в 5 сек для тестироания
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос 429 | 500 \n";

                    continue;
                } else {                                // какая то неизвестная ошибка
                    $response->throw();
                }
            }

            return $response->body();

            break;
        } while (true);
    }

    protected function fetchToAPIGet(string $url, array $header)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        $count = 0;
        do {
            $options = ['connect_timeout' => 60, 'timeout' => 180];
            $response = Http::withOptions($options)->withHeaders($header)->get($url);

            if ($response->status() >= 400) {
                if ($response->status() == 403) {
                    throw new AuthException('The token is rotten', 403);
                } elseif ($response->status() == 404) {
                    throw new Http404Exception('Not Found', 404);
                } elseif ($response->status() == 429 || $response->status() == 500) {     // error 429 слишком много запросов / error 500 сервер не доступен
                    if ($count > $this->attempts) {
                        $response->throw();
                    }

                    sleep(5);   // делаем паузу в 5 сек для тестироания
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос\n";

                    continue;
                } elseif ($response->status() == 404) {
                    yield [];
                } else {                                // какая то неизвестная ошибка
                    $response->throw();
                }
            }

            $json = $response->json();

            if (array_key_exists('list', $json)) {
                if ($count > 0) $count = 0;

                foreach ($json['list'] as $unit) {
                    yield $unit;
                }
            } else {
                yield [];
            }

            break;
        } while (true);
    }

    /**
     * запрос в API по методу /v2/product/info/list
     *
     * @param string $url полный url запроса
     * @param string $body тело запроса
     * @param array $header заголовок запроса
     * @return Generic
     */
    public function fetchToAPIFboList(string $url, string $body, array $header)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $count = 0;
        do {
            $options = ['connect_timeout' => 30, 'timeout' => 120];
            $response = Http::withOptions($options)->withHeaders($header)->withBody($body, 'application/json')->post($url);

            if ($response->status() >= 400) {
                if ($response->serverError()) { // error 500
                    if ($count > $this->attempts) {
                        $response->throw();
                    }

                    //sleep(180);  // лимит запросов свыше 1000 1 раз в 3 минуты
                    sleep(1);   // какая то проблема с Ozon, если 1000 значений запрашиваем, то постоянно ошибка 500
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос\n";

                    continue;
                } else {
                    $response->throw();
                }
            }

            $json = $response->json();

            if (array_key_exists('result', $json) && count($json['result']) > 0) {
                if ($count > 0) $count = 0;

                foreach ($json['result'] as $value) {
                    yield $value;
                }
            } else {
                yield [];
            }

            break;
        } while (true);
    }

    /**
     * запрос в API по методу /v3/product/info/stocks
     *
     * @param string $url полный url запроса
     * @param array $header заголовок запроса
     * @param int $limit лимит данных в одном запросе (макс 1000)
     * @return Generic
     */
    public function fetchToAPIStockWarehouses(string $url, array $header, int $limit)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $last_id = "";

        $count = 0;
        do {
            $body = json_encode(
                [
                    "filter" => [
                        "offer_id" => [],
                        "product_id" => [],
                        "visibility" => "ALL"
                    ],
                    "last_id" => $last_id,
                    "limit" => $limit
                ]
            );

            $options = ['connect_timeout' => 30, 'timeout' => 120];
            $response = Http::withOptions($options)->withHeaders($header)->withBody($body, 'application/json')->post($url);

            if ($response->status() >= 400) {
                if ($response->serverError()) {     // error 500
                    if ($count > $this->attempts) {
                        $response->throw();
                    }

                    sleep(20);  // лимит запросов свыше 1000 1 раз в минуту
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос\n";

                    continue;
                } else {
                    $response->throw();
                }
            }

            $json = $response->json();

            if (array_key_exists('result', $json) && $json['result']['total'] > 0) {
                if ($count > 0) $count = 0;

                foreach ($json['result']['items'] as $value) {
                    yield $value;
                }

                $last_id = $json['result']['last_id'];
                continue;
            } else {
                yield [];
            }

            break;
        } while (true);
    }

    /**
     * запрос в API по методу /v2/product/info/list
     *
     * @param string $url полный url запроса
     * @param string $body тело запроса
     * @param array $header заголовок запроса
     * @return Generic
     */
    public function fetchToAPIFboFbs(string $url, string $body, array $header)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $count = 0;

        do {
            $options = ['connect_timeout' => 30, 'timeout' => 120];
            $response = Http::withOptions($options)->withHeaders($header)->withBody($body, 'application/json')->post($url);
            if ($response->status() >= 400) {
                if ($response->serverError()) {     // error 500
                    if ($count > $this->attempts) {
                        $response->throw();
                    }

                    sleep(60);  // лимит запросов свыше 1000 1 раз в минуту
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос\n";

                    continue;
                } else {
                    $response->throw();
                }
            }

            $json = $response->json();

            if (array_key_exists('result', $json) && count($json['result']['items']) > 0) {
                if ($count > 0) $count = 0;

                foreach ($json['result']['items'] as $value) {
                    yield $value;
                }
            } else {
                yield [];
            }

            break;
        } while (true);
    }

    // /**
    //  * создание url API
    //  *
    //  * @param string $urlAPI шаблон url от API Roistat
    //  * @param string $task задача
    //  * @param string $secret
    //  * @param string $project
    //  * @param string $from
    //  * @param string $to
    //  * @return string|null
    //  */
    // protected function createUrl(string $apiURL, string $task, string $secret = '', string $project = '', string $from = '', string $to = ''): string|null
    // {
    //     if ($task === 'stock-warehouses') {
    //         return $apiURL . "/v3/product/info/stocks";
    //     } elseif ($task === 'fbo-list') {
    //         return $apiURL . "/v2/posting/fbo/list";
    //     } elseif ($task === 'ListOfGoodsID') {
    //         return $apiURL . "/v2/product/info/list";
    //     } elseif ($task === 'campaign') {
    //         return "{$apiURL}/api/client/campaign?client_secret={$secret}&client_id={$project}";
    //     } elseif ($task === 'statistics-media-compaign') {
    //         return "{$apiURL}/api/client/statistics/campaign/media?from={$from}&to={$to}";
    //     } elseif ($task === 'statistics-daily') {
    //         return "{$apiURL}/api/client/statistics/daily?client_secret={$secret}&client_id={$project}&dateFrom={$from}&dateTo={$to}";
    //     } elseif ($task === 'statistics-get-report') {
    //         return "{$apiURL}/api/client/statistics/report?UUID=";
    //     }
    //     //---
    //     return null;
    // }
}
