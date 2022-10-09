<?php

namespace App\Services\Ozon;

use App\Repositories\Export\ServiceRepository;
use App\Repositories\Ozon\OzonRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Export\JournalService;
use App\Services\Libs\BackUpFile;
use App\Services\Ozon\Processings\FboListProcessing;
use App\Services\Ozon\Traits\FetchDataFromAPI;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;

class FboListService extends BaseService implements InterfaceService
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
     * обработка по методу /v2/posting/fbo/list
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url от API Ozon
     * @param string $project идентификатор проекта
     * @param string $secret key проекта Ozon
     * @param string $task задача
     * @param string|null $from дата / время с которой делаем выборку в API
     * @param string|null $to дата / время по которую делаем выборку в API (в рабочем режиме по текущее дата / время)
     * @param integer|null $limit лимит данных в одном запросе (макс 1000)
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task, ?string $from = '', ?string $to = '', ?int $limit = 0)
    {
        $sz = new Serializer($task, $this->debug);
        $processing = new FboListProcessing($table, $typeDB, $task);

        $journal = new JournalService($typeDB, $project, $task);
        $journal->startTask();

        $service = new ServiceRepository();
        $result = $service->getProjects($typeDB, $project)->first();
        if (is_null($result)) {
            $journal->upTask('OK', 'result == null');     // обновим запись в журнале
            return;
        }

        try {
            $this->clearTable($typeDB, $project, $table, "ozon_{$result->name}_products");  // очистим таблицы перед запись нового среза данных
        } catch (Exception $e) {
            $journal->upTask('ERROR', $e->getMessage());
            outMsg($task, 'error.', $e->getMessage(), $this->debug);    // процесс прерывать не будем, понаблюдаем
        }

        //--- расчетные константы
        $url = $this->createUrl($urlAPI);
        $from = date('Y-m-d H:i:s', strtotime($from));  // $processing->getDateFrom($from, $project, 'UTC');

        $header = [
            'Content-Type' => 'application/json',
            'Client-Id' => $project,
            'Api-Key' => $secret
        ];

        $offset = 0;
        $total = 0;

        $cnt = 0;
        while (true) {
            //--- скорректируем offset, т.к. ozon не дает сдвиг более 100 000 без ошибок
            if ($offset > 100000) {
                echo "вышли на превышение, limit [ ", $limit, " ] обнулим offset\n";
                $from = $this->getDateFrom($table, $typeDB,  $project, $from, $processing->classIsField($task), 'UTC');
                $offset = 0;
            }

            $body = json_encode(
                [
                    "dir" => "ASC",
                    "filter" => [
                        "since" => formatDateTo($from, 'Y-m-d\TH:i:s.v\Z', 'UTC'),
                        "status" => "",
                        "to" => formatDateTo($to, 'Y-m-d\TH:i:s.v\Z', 'UTC'),
                    ],
                    "limit" => $limit,
                    "offset" => $offset,
                    "translit" => true,
                    "with" => [
                        "analytics_data" => true,
                        "financial_data" => true
                    ]
                ]
            );

            echo "N ", $cnt, ". limit ", $limit, " | offset ", $offset, "\n";

            $dataDB = [];
            try {
                foreach ($this->fetchToAPIFboList($url, $body, $header) as $unit) {
                    $dataDB[] = $sz->serialize($unit, $project);
                }

                if (empty($dataDB)) break;   // если массив пуст, значит больше данных нет

                $newDataDB = $processing->check($dataDB, $project);

                $saveDataDB = $this->repository->insertComby($table, $typeDB, $newDataDB);

                //--- тут создадим связь с products[]
                if (!is_null($saveDataDB)) {
                    foreach ($saveDataDB as $item) {
                        $processing->binding("ozon_{$result->name}_products", $item);
                    }
                }

                if (count($dataDB) < $limit) break;  // полученный массив меньше чем limit, значит все данные уже получены

                $length = count($dataDB);
                $offset = $offset + $length;
                $total = $total + $length;

                unset($dataDB);
                unset($newDataDB);
                unset($saveDataDB);

                $cnt++;
                echo "всего получено ", $total, "\n";
            } catch (Exception | ErrorException $e) {
                $journal->upTask('ERROR', $e->getMessage());
                outMsg($task, 'error.', $e->getMessage(), $this->debug);
                return;
            }
        }

        //--- сохраним историю в файл в стадии разработки, не хватает памяти, нужно решить вопрос работы с выделенной памятью
        // try {
        //     (new BackUpFile())->pack("ozon_{$result->name}_products", 'sku');
        //     (new BackUpFile())->pack($table, 'created_at');
        // } catch (Exception | ErrorException $e) {
        //     outMsg($task, 'error.', $e->getMessage(), $this->debug);    // тут только отправляем в телеграм уведомление об ошибке по упаковке в архив
        // }

        $journal->upTask('OK');     // обновим запись в журнале

        unset($body);
        unset($header);
        unset($sz);
        unset($processing);
    }

    public function createUrl(string $urlAPI): string
    {
        return $urlAPI . "/v2/posting/fbo/list";
    }

    /**
     * очистка таблиц
     *
     * @param string $typeDB
     * @param string $project
     * @param string $tblFboList
     * @param string $tblProduct
     * @return void
     */
    private function clearTable(string $typeDB, string $project, string $tblFboList, string $tblProduct)
    {
        try {
            DB::connection($typeDB)->table($tblFboList)->where('project_id', $project)->delete();

            if (DB::connection($typeDB)->table($tblProduct)->get()->count() > 0) {
                DB::connection($typeDB)->table($tblProduct)->where('project_id', $project)->delete();
            }
        } catch (Exception $e) {
            throw $e;
        }
    }
}
