<?php

namespace App\Services\WB;

use App\Repositories\WB\WbRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Export\JournalService;
use App\Services\WB\Processings\OrderProcessing;
use App\Services\WB\Traits\FetchDataFromAPI;
use ErrorException;
use Exception;

class OrdersService extends BaseService implements InterfaceService
{
    use FetchDataFromAPI;

    /**
     * конструктор
     *
     * @param [type] $repository
     */
    public function __construct(
        private WbRepository $repository = new WbRepository(),
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
     * @param integer|null $flag флаг, если == 0 то забираем данные в диапазоне, если == 1 то только по dateFrom
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task, ?string $from = null, ?int $flag = null)
    {
        $sz = new Serializer($task, $this->debug);
        $processing = new OrderProcessing($table, $typeDB, $task);

        $journal = new JournalService($typeDB, $project, $task);
        $journal->startTask();

        //--- если в БД есть история, то сдвигаем стартовую дату на 1 неделю назад от даты/время последней выгрузки
        $from = $this->formatDate($from, timezoneId: 'Europe/Moscow');      // приведем все к одной величине
        $fromT = $this->getDateFrom($table, $typeDB,  $project, $from, $processing->classIsField($task), 'Europe/Moscow');

        if (strtotime($from) < strtotime($fromT)) {                         // если история есть, сдвигаем старотовую дату на 90 дней назад, что бы гарантировано забрать все изменения
            $from = date('Y-m-d', strtotime($fromT) - 7776000);
        } else {                                                            // иначе забираем с даты старта из планировщика
            $from = $fromT;
        }

        $url = $this->createUrl($urlAPI, $secret, $task, $from, $flag);

        $dataDB = [];
        try {
            foreach ($this->fetchToAPI($url) as $unit) {
                $dataDB[] = $sz->serialize($unit, $project);
            }

            // проверка данных в БД и того, что получили
            $newDataDB = $processing->check($dataDB, $project);

            foreach (array_chunk($newDataDB, 1000) as $chunk) {
                $this->repository->upsertOrders($table, $typeDB, $chunk);
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

    public function createUrl(string $urlAPI, ?string $secret = null, ?string $task = null, ?string $from = null, ?int $flag = null): string
    {
        return $urlAPI . '/' . $task . '?dateFrom=' . $from . '&flag=' . $flag . '&key=' . $secret;
    }
}
