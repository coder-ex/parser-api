<?php

namespace App\Services\Roistat;

use App\Repositories\Roistat\RoistatRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Export\JournalService;
use App\Services\Roistat\Processings\DataProcessing;
use App\Services\Roistat\Traits\FetchDataFromAPI;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;

class DataService extends BaseService implements InterfaceService
{
    use FetchDataFromAPI;

    /**
     * конструктор
     *
     * @param [type] $repository
     */
    public function __construct(
        private RoistatRepository $repository = new RoistatRepository(),
    ) {
        parent::__construct();
        date_default_timezone_set('UTC');   // Roistat работает по UTC
    }

    public function __destruct()
    {
        unset($this->repository);
    }

    /**
     * получение данных по методу /project/analytics/data
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url от API 
     * @param string $project идентификатор проекта
     * @param string $secret key проекта 
     * @param string $task задача
     * @param string|null $from дата / время с которой делаем выборку в API
     * @param string|null $to дата / время по которую делаем выборку в API (в рабочем режиме по текущее дата / время)
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task, ?string $from = '', ?string $to = '')
    {
        $sz = new Serializer($task, $this->debug);
        $processing = new DataProcessing($table, $typeDB, $task);

        $journal = new JournalService($typeDB, $project, $task);
        $journal->startTask();

        try {
            DB::connection($typeDB)->table($table)->where('project_id', $project)->delete();
        } catch (Exception $e) {
            $journal->upTask('ERROR', $e->getMessage());
            outMsg($task, 'error.', $e->getMessage(), $this->debug);    // процесс прерывать не будем, понаблюдаем
        }

        //--- расчетные константы
        // $from = $processing->getDateFrom($from, $project, 'Europe/Moscow');  всегда берем начальную дату т.к. нужно прогонять всю историю
        $range = 86400 * 1;
        $diff = strtotime($to) - strtotime($from);

        $length = (($diff / $range) < 1) ? 1 : (int)($diff / $range);
        if (
            $diff % $range > 0 && $length > 1 ||
            $diff % $range > 0 && ($diff / $range) >= 1
        ) {
            $length++;
        }

        $offset = strtotime($from);

        $url = $this->createUrl($urlAPI, $task, $project, $secret);

        $complete = false;

        for ($j = 0; $j < $length; $j++) {
            $dateFROM = $offset;
            $dateTO = null;

            if (strtotime($to) < $dateFROM + $range) {
                $dateTO = strtotime($to);
                $complete = true;
            } else {
                $dateTO =
                    $dateFROM + $range;
            }

            $body = json_encode(
                [
                    "dimensions" => [
                        "marker_level_1",
                        "marker_level_2",
                        "marker_level_3",
                    ],
                    "metrics" => [
                        "marketing_cost",
                    ],
                    "period" => [
                        "from" => date('Y-m-dTH:i:s', $dateFROM),
                        "to" => date('Y-m-dTH:i:s', $dateTO),
                    ],
                    "interval" => "1d"
                ]
            );

            $header = [
                'Content-Type: application/json',
            ];

            echo "dateFROM: ", date('Y-m-d H:i:s', $dateFROM), " | dateTO: ", date('Y-m-d H:i:s', $dateTO), "\n";

            $dataDB = [];
            try {
                foreach ($this->fetchToAPIData($url, $body, $header) as $unit) {
                    $dataDB[] = $sz->serialize($unit, $project);
                }

                $newDataDB = $processing->check($dataDB, $project);

                foreach (array_chunk($newDataDB, 1000) as $chunk) {
                    $this->repository->upsertTable($table, $typeDB, $chunk);
                }
            } catch (Exception | ErrorException $e) {
                $journal->upTask('ERROR', $e->getMessage());
                outMsg($task, 'error.', $e->getMessage(), $this->debug);
                return;
            }

            $offset = $dateTO;

            unset($body);
            unset($header);
            unset($dataDB);
            unset($newDataDB);

            if ($complete) break;
        }

        $journal->upTask('OK');     // обновим запись в журнале

        unset($sz);
        unset($processing);
    }

    public function createUrl(string $urlAPI, ?string $task=null, ?string $project=null, ?string $secret=null): string
    {
        return $urlAPI . "/project/analytics/{$task}?project={$project}&key={$secret}";
    }
}
