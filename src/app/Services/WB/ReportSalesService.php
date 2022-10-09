<?php

namespace App\Services\WB;

use App\Repositories\Base\Repository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Export\JournalService;
use App\Services\WB\Processings\SaleReportProcessing;
use App\Services\WB\Traits\FetchDataFromAPI;
use ErrorException;
use Exception;

class ReportSalesService extends BaseService implements InterfaceService
{
    use FetchDataFromAPI;

    /**
     * конструктор
     *
     * @param [type] $repository
     */
    public function __construct(
        private Repository $repository = new Repository(),
    ) {
        parent::__construct();
        date_default_timezone_set('UTC');   // Ozon работает по UTC
    }

    public function __destruct()
    {
        unset($this->repository);
    }

    /**
     * получение данных по Поставки
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url от API WB
     * @param string $project идентификатор проекта
     * @param string $secret токен от магазина WB
     * @param string $task задача
     * @param string|null $from дата / время с которой делаем выборку в API
     * @param string|null $to дата / время по которую делаем выборку в API (в рабочем режиме по текущее дата / время)
     * @param integer|null $limit лимит данных в одном запросе (макс 100 000)
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task, ?string $from = null, ?string $to = null, ?int $limit = null)
    {
        $sz = new Serializer($task, $this->debug);
        $processing = new SaleReportProcessing($table, $typeDB, $task);

        $journal = new JournalService($typeDB, $project, $task);
        $journal->startTask();

        //--- расчетные константы
        $from = $this->getDateFrom($table, $typeDB,  $project, $from, $processing->classIsField($task), 'Europe/Moscow');
        $range = 86400 * 30;
        $diff = strtotime($to) - strtotime($from);

        $length = (int)($diff / $range);
        if ($diff % $range > 0) {   // т.к. округление в PHP в меньшую сторону, если есть остаток, то length + 1, что бы добрать оставшееся время
            $length++;
        }

        $offset = strtotime($from);

        $complete = false;

        for ($i = 0; $i < $length; $i++) {
            $dateFROM = $offset;
            $dateTO = null;

            if (strtotime($to) < $dateFROM + $range) {
                $dateTO = strtotime($to);
                $complete = true;
            } else {
                $dateTO = $dateFROM + $range;
            }

            echo "dateFROM: ", date('Y-m-d H:i:s', $dateFROM), " | dateTO: ", date('Y-m-d H:i:s', $dateTO), "\n";

            $url = $this->createUrl($urlAPI, $secret, $task, date('Y-m-d H:i:s', $dateFROM), $limit);
            $dataDB = [];
            try {
                foreach ($this->fetchToAPIReport($url, date('Y-m-d H:i:s', $dateTO)) as $unit) {
                    $dataDB[] = $sz->serialize($unit, $project);
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

            $offset = $dateTO;

            unset($content);
            unset($header);
            unset($dataDB);
            unset($newDataDB);

            if ($complete) break;
        }

        $journal->upTask('OK');     // обновим запись в журнале
    }

    public function createUrl(string $urlAPI, ?string $secret = null, ?string $task = null, ?string $from = null, ?int $limit = null): string
    {
        return $urlAPI . '/' . $task . '?dateFrom=' . $from . '&key=' . $secret . '&limit=' . $limit;
    }
}
