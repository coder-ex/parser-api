<?php

namespace App\Services\Roistat\Traits;

use Exception;
use Generator;
use Illuminate\Support\Facades\Http;
use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use stdClass;

/** получение данных из АПИ */
trait FetchDataFromAPI
{
    protected int $attempts = 5;

    /**
     * запрос по API по методу /project/orders/{orderId}/info на получение одного элемента
     *
     * @param string $url полный url запроса
     * @param array $header заголовок запроса
     * @return array|int
     */
    public function fetchToAPICost(string $url, array $header)
    {
        $count = 0;
        do {
            //usleep(100000);     // тайминг между запросами (10 запросов в секунду)
            $options = ['connect_timeout' => 60, 'timeout' => 160];
            $response = Http::withOptions($options)->withHeaders($header)->get($url);

            if ($response->status() >= 400) {
                if ($response->status() == 429) { // error 429 слишком много запросов
                    if ($count > $this->attempts) {
                        $response->throw();
                    }

                    sleep(60);   // делаем паузу в 1 минуту
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос [ code: 429 ] \n";

                    continue;
                } else {
                    $response->throw();
                }
            }

            $json = $response->json();

            if (is_null($json)) {
                if ($count > 3) {
                    return null;
                }

                sleep(1);   // делаем паузу в 1 секунду
                $count++;

                echo "N. ", $count, " | вышли на повторный запрос [ json == null ] \n";

                continue;
            } elseif (array_key_exists('order', $json) && count($json['order']) > 0) {
                if ($count > 0) $count = 0;

                return $json;
            }

            break;
        } while (true);

        return 0;
    }

    /**
     * запрос о всех визитах /project/site/visit/list
     *
     * @param string $task задача
     * @param string $url полный url запроса
     * @param string $body тело запроса
     * @param array $header заголовок запроса
     * @return Generic
     */
    public function fecthToAPIVisitList(string $task, string $url, string $body, array $header)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $count = 0;
        try {
            do {
                $options = ['connect_timeout' => 30, 'timeout' => 120];
                $result = Http::withOptions($options)->withBody($body, $header[0])->post($url)->throw()?->object();

                if ($result->status === 'success' && $result->total > 0) {
                    if ($count > 0) $count = 0;

                    foreach ($result->data as $value) {
                        yield $value;
                    }
                } elseif ($result->status === 'error') {
                    if ($count > $this->attempts) {
                        yield [];
                        break;
                    }

                    sleep(5);
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос\n";

                    continue;
                } else {
                    yield [];
                }

                break;
            } while (true);
        } catch (Exception $e) {
            throw $e;
        } finally {
            $result = NULL;
        }
    }

    /**
     * запрос в API по методу /project/integration/order/list
     *
     * @param string $url полный url запроса
     * @param string $body тело запроса
     * @param array $header заголовок запроса
     * @return mixed
     */
    public function fetchToAPIListIntegration(string $url, string $body, array $header): mixed
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $count = 0;
        try {
            do {
                $options = ['connect_timeout' => 60, 'timeout' => 180];
                $result = Http::withOptions($options)->withBody($body, $header[0])->post($url)->throw()?->json();

                if ($result['status'] === 'success' && $result['total'] > 0) {
                    if ($count > 0) $count = 0;

                    foreach ($result['data'] as $value) {
                        yield $value;
                    }
                } elseif ($result['status'] === 'error') {
                    if ($count > $this->attempts) {
                        yield [];
                        break;
                    }

                    sleep(60);  // лимит запросов свыше 1000 1 раз в минуту
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос [ fetchToAPIListIntegration ]\n";

                    continue;
                } else {
                    yield [];
                }

                break;
            } while (true);
        } catch (Exception $e) {
            throw $e;
        } finally {
            $result = NULL;
        }
    }

    /**
     * запрос по API по методу /project/site/visit/list на получение одного элемента
     *
     * @param string $url полный url запроса
     * @param string $body тело запроса
     * @param array $header заголовок запроса
     * @return stdClass|int
     */
    public function fetchToAPIOne(string $url, string $body, array $header): stdClass|int
    {
        $count = 0;
        try {
            do {
                $result = Http::withBody($body, $header[0])->post($url)->throw()?->object();

                if ($result->status === 'success') {
                    if ($count > 0) $count = 0;

                    return $result;
                } elseif ($result->status === 'error') {
                    if ($count > $this->attempts) {
                        break;
                    }

                    sleep(3);
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос [ fetchToAPIOne ] \n";

                    continue;
                }

                break;
            } while (true);
        } catch (Exception $e) {
            throw $e;
        } finally {
            unset($result);
        }

        return 0;
    }

    /**
     * запрос в API по методу /user/projects
     *
     * @param string $task задача
     * @param string $url полный url запроса
     * @param string $body тело запроса
     * @param array $header заголовок запроса
     * @return mixed
     */
    public function fethcToAPIProjects(string $task, string $url, array $header): mixed
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $count = 0;
        try {
            do {
                $result = Http::accept($header[0])->get($url)->throw()?->object();

                if ($result->status === 'success' && count($result->projects) > 0) {
                    if ($count > 0) $count = 0;

                    foreach ($result->projects as $value) {
                        yield $value;
                    }
                } elseif ($result->status === 'error') {
                    if ($count > $this->attempts) {
                        yield [];
                        break;
                    }

                    sleep(5);
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос\n";

                    continue;
                } else {
                    yield null;
                }

                break;
            } while (true);
        } catch (Exception $e) {
            throw $e;
        } finally {
            $result = NULL;
        }
    }

    /**
     * запрос в API по методу /project/analytics/data
     *
     * @param string $url полный url запроса
     * @param string $body тело запроса
     * @param array $header заголовок запроса
     * @return mixed
     */
    public function fetchToAPIData(string $url, string $body, array $header): mixed
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $result = null;

        $count = 0;
        try {
            do {
                $result = Http::withBody($body, $header[0])->post($url)->throw()?->object();

                if ($result->status === 'success' && count($result->data) > 0) {
                    foreach ($result->data as $value) {
                        foreach ($value->items as $unit) {
                            $unit->dateFrom = $value->dateFrom;
                            $unit->dateTo = $value->dateTo;
                            yield $unit;
                        }
                    }
                } elseif ($result->status === 'error') {
                    if ($count > $this->attempts) {
                        //yield [];
                        break;
                    }

                    sleep(5);
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос\n";

                    continue;
                } else {
                    yield [];
                }

                break;
            } while (true);
        } catch (Exception $e) {
            throw $e;
        } finally {
            $result = NULL;
        }
    }

    /**
     * запрос о всех визитах /project/analytics/list-orders
     *
     * @param string $task задача
     * @param string $url полный url запроса
     * @param string $body тело запроса
     * @param array $header заголовок запроса
     * @return Generic
     */
    public function requestsToAPIListOrders(string $task, string $url, string $body, array $header)
    {
        //--- проблемы с памятью
        $time_limit = ini_get('max_execution_time');
        $memory_limit = ini_get('memory_limit');
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $result = null;

        $count = 0;
        try {
            do {
                $options = ['connect_timeout' => 30, 'timeout' => 120];
                $result = Http::withOptions($options)->withBody($body, $header[0])->post($url)->throw()?->json();

                if ($result['status'] === 'success' && $result['total'] > 0) {
                    if ($count > 0) $count = 0;

                    foreach ($result['orders'] as $value) {
                        yield $value;
                    }
                } elseif ($result['status'] === 'error') {
                    if ($count > $this->attempts) {
                        yield [];
                        break;
                    }

                    sleep(5);
                    $count++;

                    echo "N. ", $count, " | вышли на повторный запрос\n";

                    continue;
                } else {
                    yield [];
                }

                break;
            } while (true);
        } catch (Exception $e) {
            throw $e;
        } finally {
            $result = NULL;
        }
    }

    /**
     * создание url API
     *
     * @param string $urlAPI шаблон url от API Roistat
     * @param string $task задача
     * @param string $project идентификатор проекта
     * @param string $secret key проекта Roistat
     * @return string|null
     */
    // protected function createUrl(string $apiURL, string $task, string|null $project, string $secret): string|null
    // {
    //     if ($task === 'data') {
    //         return $apiURL . "/project/analytics/{$task}?project={$project}&key={$secret}";
    //     } elseif ($task === 'list-integration') {
    //         return $apiURL . "/project/integration/order/list?project={$project}&key={$secret}";
    //     }
    //     // elseif ($task === 'visit-list') {
    //     //     return $apiURL . "/project/site/visit/list?project={$project}&key={$secret}";
    //     // }
    //     //---
    //     return null;
    // }
}
