<?php

namespace App\Services\WB;

use App\Repositories\WB\WbRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Export\JournalService;
use App\Services\WB\Processings\IncomeProcessing;
use App\Services\WB\Traits\FetchDataFromAPI;
use ErrorException;
use Exception;

class IncomesService extends BaseService implements InterfaceService
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
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task, ?string $from = '')
    {
        $sz = new Serializer($task, $this->debug);
        $processing = new IncomeProcessing($table, $typeDB, $task);

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

        $url = $this->createUrl($urlAPI, $secret, $task, $from);

        $dataDB = [];
        try {
            foreach ($this->fetchToAPI($url) as $unit) {
                $dataDB[] = $sz->serialize($unit, $project);
            }

            $newDataDB = $processing->check($dataDB, $project);

            foreach (array_chunk($newDataDB, 1000) as $unit) {
                $this->repository->upsertIncomes($table, $typeDB, $unit);
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

    public function createUrl(string $urlAPI, ?string $secret = null, ?string $task = null, ?string $dateFrom = null): string
    {
        return $urlAPI . '/' . $task . '?dateFrom=' . $dateFrom . '&key=' . $secret;
    }
}
