<?php

namespace App\Services\Ozon;

use App\Repositories\Ozon\OzonRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Exceptions\AuthException;
use App\Services\Export\JournalService;
use App\Services\Ozon\Traits\FetchDataFromAPI;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;

/** статистика по продуктовым кампаниям */
class StatExpenseCampaignService extends BaseService implements InterfaceService
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
     * обработка по методу get /api/client/statistics/expense
     *  1. в данной выгрузке есть особенность: данные Ozon по этому методу отдает максимум за прошлые сутки (вчерашний from <= to)
     *  2. данные забираются в цикле, с from + 86400, т.е. переходим на следующие сутки
     *  3. учитывая п.1, выгрузку размещать в начале следующих суток в планировщике, что бы получить актуальные данные за текущие сутки
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url от API Ozon
     * @param string $project идентификатор проекта
     * @param string $secret ключ к кабинету клиента (key | token и т.д.)
     * @param string $task задача
     * @param string|null $from с какой даты/время
     * @param string|null $to по какую дату/время (всегда по текущее дату/время)
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task, ?string $from = '', ?string $to = '')
    {
        $sz = new Serializer($task, $this->debug);

        $journal = new JournalService($typeDB, $project, $task);
        $journal->startTask();

        //--- если в БД есть история, то сдвигаем стартовую дату на 1 сутки т.к. данные Ozon по этому методу отдает за сутки from == to
        $from = date('Y-m-d', strtotime($from));        // приведем все к одной величине
        $fromT = date('Y-m-d', strtotime($this->getDateFrom($table, $typeDB, $project, $from, 'date', 'Europe/Moscow')));

        if (strtotime($from) < strtotime($fromT)) {     // если история есть, сдвигаем старотовую дату на сутки вперед, т.к. дубли по этому дню не нужны
            $from = date('Y-m-d', strtotime($fromT) + 86400);
        } else {                                        // иначе забираем с даты старта из планировщика
            $from = $fromT;
        }

        $to = date('Y-m-d', strtotime($to));

        if (strtotime($from) > strtotime($to)) {
            echo "на участке from: ", date('Y-m-d H:i:s', strtotime($from)), " | to: ", date('Y-m-d H:i:s', strtotime($to)), " размер данных == 0\n";
            $journal->upTask('OK', 'размер данных == 0');     // обновим запись в журнале
            return;
        }

        try {
            //--- расчетные константы
            $range = 86400 * 1;
            $diff = strtotime($to) - strtotime($from);

            $length = (($diff / $range) < 1) ? 1 : (int)($diff / $range) + 1;

            $offset = strtotime($from);

            $cntToken = 0;
            $flagToken = true;
            while (true) {

                //--- получим токен если флаг взведен
                if ($flagToken) {
                    $header = [
                        'Content-Type' => 'application/json',
                        'Accept' => '*/*',
                        'Authorization' => 'Bearer ' . OzonToken::getToken($urlAPI, $project, $secret),
                    ];

                    $flagToken = false;
                }

                $dateFROM = $offset;
                $dateTO = null;

                if (strtotime($to) <= ($dateFROM - $range)) {  // осталась одна итерация по range
                    $dateTO = strtotime($to);
                    break;
                } else {
                    $dateTO = $dateFROM;
                }

                $url = $this->createUrl($urlAPI, date('Y-m-d', $dateFROM), date('Y-m-d', $dateTO));

                echo "dateFROM: ", date('Y-m-d H:i:s', $dateFROM), " | dateTO: ", date('Y-m-d H:i:s', $dateTO), "\n";

                try {
                    $file = $this->fetchToAPIFile($url, $header);
                } catch (AuthException $e) {
                    if ($cntToken > 3) {
                        throw new Exception("токен протух > 3 - [ {$e->getMessage()} ]", $e->getCode());
                    }

                    $cntToken++;
                    $flagToken = true;
                    continue;
                }

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

                foreach (array_chunk($dataDB, 1000) as $unit) {
                    $this->repository->insertTable($table, $typeDB, $unit);
                }

                //--- запишем самую свежую дату в кэш для оптимизации метода, срок экспирации 30 суток
                $date = DB::connection($typeDB)->table($table)->where('project_id', $project)->orderBy('date', 'desc')->first()?->date;
                if (!is_null($date)) {
                    cachePut($table, $project, $date, "{$task} : {$project}");
                }

                $offset = $offset + $range;

                unset($dataDB);
            }
        } catch (Exception | ErrorException $e) {
            $journal->upTask('ERROR', $e->getMessage());
            outMsg($task, 'error.', $e->getMessage(), $this->debug);
            return;
        }

        $journal->upTask('OK');     // обновим запись в журнале

        unset($dataDB);
    }

    public function createUrl(string $urlAPI, ?string $from = '', ?string $to = ''): string
    {
        return "{$urlAPI}/api/client/statistics/expense?dateFrom={$from}&dateTo={$to}";
    }
}
