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
    protected int $cnt403 = 0;
    protected int $cnt404 = 0;
    protected int $cnt429 = 0;
    protected int $cnt500 = 0;
    protected int $cnt504 = 0;

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

        do {
            $options = ['connect_timeout' => 30, 'timeout' => 120];
            $response = Http::withOptions($options)->withHeaders($header)->withBody($body, 'application/json')->post($url);

            if(!$this->handlerResponse($response)) continue;
            //---
            return $response->body();

        } while (true);
    }

    protected function fetchToAPIFile(string $url, array $header)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        do {
            $options = ['connect_timeout' => 60, 'timeout' => 180];
            $response = Http::withOptions($options)->withHeaders($header)->get($url);

            if(!$this->handlerResponse($response)) continue;

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

            if(!$this->handlerResponse($response)) continue;

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

            if(!$this->handlerResponse($response)) continue;

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

    /**
     * очистка cntXXX переменных
     *
     * @return void
     */
    private function cntClear()
    {
        if ($this->cnt403) $this->cnt403 = 0;
        if ($this->cnt404) $this->cnt404 = 0;
        if ($this->cnt429) $this->cnt429 = 0;
        if ($this->cnt500) $this->cnt500 = 0;
        if ($this->cnt504) $this->cnt504 = 0;
    }

    /**
     * обработчик response
     *
     * @param \Illuminate\Http\Client\Response $response объект ответа сервера
     * @return boolean
     */
    private function handlerResponse(\Illuminate\Http\Client\Response &$response): bool
    {
        if ($response->status() >= 400) {
            if ($response->status() == 403) {
                $this->cntClear();
                throw new AuthException('The token is rotten', 403);
            } elseif ($response->status() == 404) {
                if ($this->cnt404 > 5) {
                    $this->cntClear();
                    throw new Http404Exception("сервер отвечает, но данных не отдает, сделано {$this->cnt404} попыток", 404);
                }

                sleep(1);   // делаем паузу в 1 сек
                $this->cnt404++;

                echo "N. ", $this->cnt404, " | вышли на повторный запрос 404 \n";

                return false;

            } elseif ($response->status() == 429) {     // error 429 слишком много запросов
                if ($this->cnt429 > $this->attempts) {
                    $this->cntClear();
                    $response->throw();
                }

                sleep(5);   // делаем паузу в 5 сек для тестироания
                $this->cnt429++;

                echo "N. ", $this->cnt429, " | вышли на повторный запрос 429 \n";

                return false;
            } elseif ($response->status() == 500) {     // error 500 сервер не доступен
                if ($this->cnt500 > $this->attempts) {
                    $this->cntClear();
                    $response->throw();
                }

                sleep(5);   // делаем паузу в 5 сек для тестироания
                $this->cnt500++;

                echo "N. ", $this->cnt500, " | вышли на повторный запрос 500 \n";

                return false;
            } elseif ($response->status() == 504) {     // error 504 промежуточный сервер (шлюз) не доступен
                if ($this->cnt504 > $this->attempts) {
                    $this->cntClear();
                    $response->throw();
                }

                sleep(5);   // делаем паузу в 5 сек для тестироания
                $this->cnt504++;

                echo "N. ", $this->cnt504, " | вышли на повторный запрос 504 \n";

                return false;
            } else {                                // какая то неизвестная ошибка
                $this->cntClear();
                $response->throw();
            }
        }

        $this->cntClear();
        return true;
    }
}
