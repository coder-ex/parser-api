<?php

namespace App\Services\Ozon;

use App\Repositories\Ozon\OzonRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Exceptions\AuthException;
use App\Services\Exceptions\Http404Exception;
use App\Services\Export\JournalService;
use App\Services\Ozon\Traits\FetchDataFromAPI;
use Illuminate\Support\Str;
use ErrorException;
use Exception;

/** статистика по продуктовым кампаниям */
class StatFoodCampaignService extends BaseService implements InterfaceService
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
     * обработка по методу get /api/client/statistics/campaign/media
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

        $from = date('Y-m-d\TH:i:s.v\Z', strtotime($this->getDateFrom($table, $typeDB, $project, $from, 'published_at', 'UTC')) + 86400);
        $to = date('Y-m-d\TH:i:s.v\Z', strtotime($to));     // метод допускает заброс в будущее по времени $to (плюс сутки минус 1 сек)

        if (strtotime($from) >= strtotime($to)) {
            echo "на участке from: ", date('Y-m-d H:i:s', strtotime($from)), " | to: ", date('Y-m-d H:i:s', strtotime($to)), " размер данных == 0\n";
            $journal->upTask('OK', 'размер данных == 0');     // обновим запись в журнале
            return;
        }

        try {
            //--- расчетные константы
            $range = 86400 * 1;
            $diff = strtotime($from) % 86400;
            $dateFROM = $dateTO = 0;

            $offset = strtotime($from);

            while (true) {
                if ($diff == 0) {
                    if ($offset >= strtotime($to)) {
                        break;
                    }

                    $dateFROM = $offset;
                    $dateTO = $dateFROM + $range -1;
                } elseif ($diff > 0) {
                    $dateFROM = $offset;
                    $dateTO = $dateFROM + $range - $diff -1;
                    $diff = 0;
                }

                if ($dateTO > strtotime($to)) {
                    $dateTO = strtotime($to);
                }

                $url = $this->createUrl($urlAPI, date('Y-m-d\TH:i:s.v\Z', $dateFROM), date('Y-m-d\TH:i:s.v\Z', $dateTO));

                echo "dateFROM: ", date('Y-m-d H:i:s', $dateFROM), " | dateTO: ", date('Y-m-d H:i:s', $dateTO), "\n";

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

                    try {
                        $file = $this->fetchToAPIFile($url, $header);

                        if ($cntToken) $cntToken = 0;
                        break;
                    } catch (AuthException $e) {
                        if ($cntToken > 3) {
                            throw new Exception("токен протух, сделано {$cntToken} попытки - [ {$e->getMessage()} ]", $e->getCode());
                        }

                        $cntToken++;
                        $flagToken = true;
                        continue;
                    }
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

                    $row = date('Y-m-d H:i:s', $dateFROM) . ';' . $row;
                    $dataDB[] = [
                        ...$sz->serialize(explode($sep, $row), $project),
                        'id' => Str::uuid()
                    ];
                }

                foreach (array_chunk($dataDB, 1000) as $unit) {
                    $this->repository->insert($table, $typeDB, $unit);
                }

                //--- запишем самую свежую дату в кэш для оптимизации метода, срок экспирации 30 суток
                //cachePut($table, $project, date('Y-m-d H:i:s', $dateTO - 86400), "{$task} : {$project}");

                $offset = $offset + $range;

                unset($dataDB);
            }
        } catch (Http404Exception | Exception | ErrorException $e) {
            $journal->upTask('ERROR', $e->getMessage());
            outMsg($task, 'error.', $e->getMessage(), $this->debug);
            return;
        }

        $journal->upTask('OK');     // обновим запись в журнале

        unset($dataDB);
    }

    public function createUrl(string $urlAPI, ?string $from = '', ?string $to = ''): string
    {
        return "{$urlAPI}/api/client/statistics/campaign/product?from={$from}&to={$to}";
    }
}
