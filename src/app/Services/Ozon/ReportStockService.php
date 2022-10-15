<?php

namespace App\Services\Ozon;

use App\Models\Export\ExportService;
use App\Repositories\Export\ServiceRepository;
use App\Repositories\Ozon\OzonRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Exceptions\Http500Exception;
use App\Services\Export\JournalService;
use App\Services\Ozon\Traits\FetchDataFromAPI;
use Illuminate\Support\Str;
use ErrorException;
use Exception;

class ReportStockService extends BaseService implements InterfaceService
{
    use FetchDataFromAPI;

    /**
     * конструктор
     *
     * @param [type] $repository
     */
    public function __construct(
        private OzonRepository $repository = new OzonRepository(),
    ) {
        parent::__construct();
        date_default_timezone_set('UTC');   // Ozon работает по UTC
    }

    public function __destruct()
    {
        unset($this->repository);
    }

    /**
     * обработка Отчет об остатках [ по методу POST https://api-seller.ozon.ru/v1/report/stock/create ]
     * 
     * шаг 1: https://api-seller.ozon.ru/v1/report/stock/create
     * шаг 2: https://api-seller.ozon.ru/v1/report/info
     * 
     *  1. получаем на первом шаге id
     *  2. на вотором шаге отправляем id с п.1 и получаем ссылку на файл
     *  3. получаем файл
     *  4. обрабатываем файл csv и пишем в таблицы
     *  5. т.к. данные отдаются срезом за день, делаем накопительную историю с отметкой даты выгрузки
     *  6. в запросе даты/время нет, получаем все что отдает Ozon
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url от API Ozon
     * @param string $project идентификатор проекта
     * @param string $secret key проекта Ozon
     * @param string $task задача
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task)
    {
        $sz = new Serializer($task, $this->debug);

        $journal = new JournalService($typeDB, $project, $task);
        $journal->startTask();

        $header = [
            'Content-Type' => 'application/json',
            'Client-Id' => $project,
            'Api-Key' => $secret
        ];

        try {
            $code = $this->fetchID($task, $urlAPI, $header);    //--- шаг 1
            $link = $this->fetchLINK($urlAPI, $header, $code);  //--- шаг 2
            $file = $this->fetchToAPIFile($link, $header);

            // парсим csv
            $sep = ';';
            $rows = explode(PHP_EOL, $file);
            $flag = true;

            $dataDB = [];
            $name = [];
            foreach ($rows as $row) {
                if ($flag) {
                    $name = explode($sep, $row);
                    $flag = false;
                    continue;
                }

                if ($row === "") continue;

                $record = $this->clearingRows($row, "\";\"");
                $dataDB[] = [
                    ...$sz->serialize($record, $project, $name),
                    'id' => Str::uuid(),
                ];
            }

            //--- вырезаем json из массива и собираем данные в массив по json для записи
            $serviceRepository = new ServiceRepository();
            $name_project = $serviceRepository->getProjects($typeDB, $project)?->first()?->name;

            $catalogDB = [];
            foreach ($dataDB as $key => $value) {
                $el = json_decode($value['catalog_json'], true);

                foreach ($el as $item) {
                    $catalogDB[] = [
                        'id' => Str::uuid(),
                        'name' => $item['name'],
                        'value' => $item['value'],
                        "fk_ozon_{$name_project}_report_stocks_id" => $dataDB[$key]['id'],
                    ];
                }

                unset($dataDB[$key]['catalog_json']);
            }

            //--- пишем report-stocks
            foreach (array_chunk($dataDB, 1000) as $chunk) {
                $this->repository->insert($table, $typeDB, $chunk);
            }

            //--- пишем catalog-report-stocks
            foreach (array_chunk($catalogDB, 1000) as $chunk) {
                $this->repository->insert("ozon_{$name_project}_catalog_report_stocks", $typeDB, $chunk);
            }
        } catch (Exception | ErrorException | Http500Exception $e) {
            $journal->upTask('ERROR', $e->getMessage());
            outMsg($task, 'error.', $e->getMessage(), $this->debug);
            return;
        }

        $journal->upTask('OK');     // обновим запись в журнале

        unset($dataDB);
        unset($newDataDB);
    }

    public function createUrl(string $urlAPI, ?string $type = ''): string
    {
        if ($type === 'report-stocks') {
            return "{$urlAPI}/v1/report/stock/create";
        }
        //---
        return "{$urlAPI}/v1/report/info";
    }

    /**
     * очистка не стандартной строки
     *
     * @param string $val строка для очистки
     * @param string $sep сепаратор разделения на значения
     * @return array
     */
    private function clearingRows(string $val, string $sep): array
    {
        $result = explode($sep, $val);
        return str_replace(["\""], "", $result);
    }

    /**
     * обработка - шаг 2
     *
     * @param string $urlAPI шаблон url от API
     * @param array $header заголовок для запроса
     * @param string $code идентификатор для запроса
     * @return string
     */
    private function fetchLINK(string $urlAPI, array $header, string $code): string
    {
        $attempt = 5;       // число попыток обойти ошибки
        $count = 0;
        $pause = 2;         // начальная пауза в сек
        $body = json_encode(["code" => $code]);
        $url = $this->createUrl($urlAPI);

        while (true) {
            try {
                $response = $this->fetchToAPIOzonOne($url, $body, $header);
                $result = json_decode($response)?->result;

                if ($result->status === 'success') {
                    return $result->file;
                } else {
                    if ($count > $attempt) throw new Exception("mesage: файл не получен, ошибок нет, status {$result->status}, число попыток {$count} | последняя пауза == {$pause}");

                    sleep($pause);
                    $count++;
                    $pause *= 2;
                    continue;
                }

                //--- если тут, значит что то пошло не так, передаем ошибку далее по стеку
                throw new Exception("mesage: что то пошло не так, stepTwo()");
            } catch (Exception | ErrorException $e) {
                $error = $e->getCode();
                if ($error >= 400) {
                    if ($error == 500) {    // если сервер недоступен, подождем 2 сек
                        sleep($pause);
                    }

                    $pause *= 2;  // на следующую попытку увеличим время 
                    $count++;
                    continue;
                }

                //--- если тут, значит что то пошло не так, передаем ошибку далее по стеку
                if ($count > $attempt) throw new Http500Exception($e->getMessage());
            }
        }
    }

    /**
     * обработка - шаг 1
     * @param string $urlAPI шаблон url от API
     * @param array $header заголовок для запроса
     * @return string идентификатор на следующий запрос
     */
    private function fetchID(string $task, string $urlAPI, array $header): string
    {
        $attempt = 5;   // число попыток обойти ошибки
        $count = 0;
        $pause = 2;     // начальная пауза в сек
        $body = json_encode(["language" => "DEFAULT"]);
        $url = $this->createUrl($urlAPI, $task);

        while (true) {
            try {
                $response = $this->fetchToAPIOzonOne($url, $body, $header);
                return json_decode($response)?->result?->code;
            } catch (Exception | ErrorException $e) {
                $error = $e->getCode();
                if ($error >= 400) {
                    if ($error == 500) {    // если сервер недоступен, подождем 2 сек
                        sleep($pause);
                    }

                    $pause *= 2;            // на следующую попытку увеличим время 
                    $count++;
                    continue;
                }

                //--- если тут, значит что то пошло не так, передаем ошибку далее по стеку
                if ($count > $attempt) throw new Http500Exception($e->getMessage());
            }
        }
    }
}
