<?php

namespace App\Services\Ozon;

use App\Repositories\Ozon\OzonRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Export\JournalService;
use App\Services\Ozon\Processings\StatDailyProcessing;
use App\Services\Ozon\Traits\FetchDataFromAPI;
use ErrorException;
use Exception;

class StatDailyService extends BaseService implements InterfaceService
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
     * обработка по методу GET https://performance.ozon.ru/api/client/statistics/daily
     *  1. в данной выгрузке есть особенность: данные отдаются не полностью за текущие сутки т.к. берем их не в 24:00:00
     *  2. данные забираются за один раз, не в цикле, с захватом прошлых суток
     *  3. в методе check() реализован механизм удаления данных за крайнюю дату выгрузки, учитывая п.1
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url от API Ozon
     * @param string $project идентификатор проекта
     * @param string $secret key проекта Ozon
     * @param string $task задача
     * @param string|null $from дата / время с которой делаем выборку в API
     * @param string|null $to дата / время по которую делаем выборку в API (в рабочем режиме по текущее дата / время)
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task, ?string $from = '', ?string $to = '')
    {
        $sz = new Serializer($task, $this->debug);
        $processing = new StatDailyProcessing($table, $typeDB, $task);

        $journal = new JournalService($typeDB, $project, $task);
        $journal->startTask();

        $from = date('Y-m-d', strtotime($this->getDateFrom($table, $typeDB,  $project, $from, $processing->classIsField($task), 'Europe/Moscow')));
        $to = date('Y-m-d', strtotime($to));

        try {
            $header = [
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
                'Authorization' => 'Bearer ' . OzonToken::getToken($urlAPI, $project, $secret),
            ];

            $url = $this->createUrl($urlAPI, $from, $to);

            $file = $this->fetchToAPIFile($url, $header);

            //--- парсим csv
            $sep = ';';
            $rows = explode(PHP_EOL, $file);
            $flag = true;

            $dataDB = [];
            foreach ($rows as $row) {
                if ($flag) {
                    $flag = false;
                    continue;
                }

                if ($row === "") continue;

                $dataDB[] = $sz->serialize(explode($sep, $row), $project);
            }

            $newDataDB = $processing->check($dataDB, $project);

            foreach (array_chunk($newDataDB, 1000) as $unit) {
                $this->repository->insertTable($table, $typeDB, $unit);
            }
        } catch (Exception | ErrorException $e) {
            $journal->upTask('ERROR', $e->getMessage());
            outMsg($task, 'error.', $e->getMessage(), $this->debug);
            return;
        }

        $journal->upTask('OK');     // обновим запись в журнале

        unset($dataDB);
        unset($newDataDB);
    }

    public function createUrl(string $urlAPI, ?string $from = '', ?string $to = ''): string
    {
        return "{$urlAPI}/api/client/statistics/daily?dateFrom={$from}&dateTo={$to}";
    }
}
