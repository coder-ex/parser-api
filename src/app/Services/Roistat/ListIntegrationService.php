<?php

namespace App\Services\Roistat;

use App\Repositories\Roistat\RoistatRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Export\JournalService;
use App\Services\Roistat\Processings\ListIntegrationProcessing;
use App\Services\Roistat\Traits\FetchDataFromAPI;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;

class ListIntegrationService extends BaseService implements InterfaceService
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
     * получение данных по методу /project/integration/order/list
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url от API 
     * @param string $project идентификатор проекта
     * @param string $secret key проекта 
     * @param string $task задача
     * @param string|null $from дата / время с которой делаем выборку в API
     * @param string|null $to дата / время по которую делаем выборку в API (в рабочем режиме по текущее дата / время)
     * @param int|null $limit для данного метода оптимально 1000
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task, ?string $from = null, ?string $to = null, ?int $limit = null)
    {
        $sz = new Serializer($task, $this->debug);
        $processing = new ListIntegrationProcessing($table, $typeDB, $task);

        $journal = new JournalService($typeDB, $project, $task);
        $journal->startTask();

        try {
            DB::connection($typeDB)->table($table)->where('project_id', $project)->delete();
        } catch (Exception $e) {
            $journal->upTask('ERROR', $e->getMessage());
            outMsg($task, 'error.', $e->getMessage(), $this->debug);    // процесс прерывать не будем, понаблюдаем
        }

        //--- расчетные константы
        //$from = $this->getDateFrom($table, $typeDB,  $project, $from, $processing->classIsField($task), 'Europe/Moscow');
        $url = $this->createUrl($urlAPI, $project, $secret);

        $body = json_encode(
            [
                "filters" => [
                    "and" => [
                        [
                            "creation_date",
                            ">",
                            $this->formatDate(date: $from, timezoneId: 'UTC')
                            //date('Y-m-dTH:i:s', strtotime($from))
                        ],
                        [
                            "creation_date",
                            "<",
                            $this->formatDate(date: $to, timezoneId: 'UTC')
                            //date('Y-m-dTH:i:s', strtotime($to)),
                        ],
                        [
                            "status",
                            "<",
                            "3"
                        ]
                    ]

                ],
                "extend" => [
                    "visit"
                ],
                "limit" => 1,
                "offset" => 0
            ]
        );

        $header = ['Content-Type: application/json'];

        $total = $this->fetchToAPIOne($url, $body, $header)->total;

        if ($total == 0) {
            echo "на участке from: ", $this->formatDate(date: $from, timezoneId: 'UTC'), " | to: ", $this->formatDate(date: $to, timezoneId: 'UTC'), " размер данных == 0\n";
            $journal->upTask('OK', 'размер данных == 0');     // обновим запись в журнале
            return;
        }

        $offset = ($total > $limit) ? $total - $limit : 0;

        $diff = $total % $limit;

        if ($total < $limit) {
            $limit = $diff;
        }

        $cnt = 0;
        while ($offset >= 0) {
            $body = json_encode(
                [
                    "filters" => [
                        "and" => [
                            [
                                "creation_date",
                                ">",
                                //date('Y-m-dTH:i:s', strtotime($from))
                                $this->formatDate(date: $from, timezoneId: 'UTC')
                            ],
                            [
                                "creation_date",
                                "<",
                                //date('Y-m-dTH:i:s', strtotime($to)),
                                $this->formatDate(date: $to, timezoneId: 'UTC')
                            ],
                            [
                                "status",
                                "<",
                                "3"
                            ]
                        ]

                    ],
                    "extend" => [
                        "visit"
                    ],
                    "limit" => $limit,
                    "offset" => $offset
                ]
            );

            echo "N ", $cnt, ". limit ", $limit, " | offset ", $offset, " край ", $total - $offset, "\n";

            $dataDB = [];
            try {
                foreach ($this->fetchToAPIListIntegration($url, $body, $header) as $unit) {
                    $dataDB[] = $sz->serialize($unit, $project);
                }

                $newDataDB = $processing->check($dataDB, $project);

                foreach (array_chunk($newDataDB, 1000) as $chunk) {
                    $this->repository->insertTable($table, $typeDB, $chunk);
                }
            } catch (Exception | ErrorException $e) {
                $journal->upTask('ERROR', $e->getMessage());
                outMsg($task, 'error.', $e->getMessage(), $this->debug);
                return;
            }

            if ($diff > 0) {
                $offset = (($offset - $limit) > 0) ? $offset - $limit : $offset - $diff;
            } else {
                $offset = $offset - $limit;
            }

            if ($offset == 0) {
                $limit = $diff;
            }

            unset($dataDB);
            unset($newDataDB);

            $cnt++;
            echo "остаток ", $offset, "\n";
        }

        $journal->upTask('OK');     // обновим запись в журнале

        unset($body);
        unset($header);
        unset($sz);
        unset($processing);
    }

    public function createUrl(string $urlAPI, ?string $project = null, ?string $secret = null): string
    {
        return "{$urlAPI}/project/integration/order/list?project={$project}&key={$secret}";
    }
}
