<?php

namespace App\Services\Ozon;

use App\Repositories\Ozon\OzonRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Export\JournalService;
use App\Services\Ozon\Traits\FetchDataFromAPI;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;

class StockWarehouseService extends BaseService implements InterfaceService
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
     * обработка по методу /v3/product/info/stocks
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url от API Ozon
     * @param string $project идентификатор проекта
     * @param string $secret key проекта Ozon
     * @param string $task задача
     * @param integer|null $limit лимит данных в одном запросе (макс 1000)
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task, ?int $limit = 0)
    {
        $journal = new JournalService($typeDB, $project, $task);
        $journal->startTask();

        try {
            $dateCach = cacheGet($table, $project);
            if (!is_null($dateCach)) {
                $diff = strtotime($dateCach) - (strtotime($dateCach) % 86400);
                if (strtotime(date('Y-m-d')) == $diff) {
                    $journal->upTask('OK');         // обновим запись в журнале
                    return;
                }
            } else {
                $oldData = DB::connection($typeDB)->table($table)->where('project_id', $project)->orderBy('date', 'desc')->first();
                if (!is_null($oldData)) {
                    $diff = strtotime($oldData->{'date'}) - (strtotime($oldData->{'date'}) % 86400);
                    if (strtotime(date('Y-m-d')) == $diff) {
                        $journal->upTask('OK');     // обновим запись в журнале
                        return;
                    }
                }
            }
        } catch (Exception | ErrorException $e) {
            $journal->upTask('ERROR', $e->getMessage());
            outMsg($task, 'error.', $e->getMessage(), $this->debug);
            return;
        }

        $sz = new Serializer($task, $this->debug);

        //--- расчетные константы
        $url = $this->createUrl($urlAPI, $task);

        $header = [
            'Content-Type' => 'application/json',
            'Client-Id' => $project,
            'Api-Key' => $secret
        ];

        $dataDB = [];
        try {
            foreach ($this->fetchToAPIStockWarehouses($url, $header, $limit) as $unit) {
                $dataDB[] = $sz->serialize($unit, $project);
            }

            // 1. вытащить из data все offer_id
            // 2. собрать запрос, отправить и получить dataFBx[]
            // 3. в цикле перебрать data и обработать - выбрать по offer_id из dataFBx нужный оффер
            // 4. из оффер вытащить fbo_sku, fbs_sku, price (лежат в корне) и вставить в itemData

            $offerID = [];
            foreach ($dataDB as $key => $value) {
                if (!array_key_exists('offer_id', $value)) {
                    unset($dataDB[$key]);
                    continue;
                }

                $offerID[] = $value['offer_id'];
            }

            //--- получим url по /v2/product/info/list
            $url = $this->createUrl($urlAPI, 'ListOfGoodsID');
            $body = json_encode(
                [
                    "offer_id" => $offerID,
                    "product_id" => [],
                    "sku" => []
                ]
            );

            foreach ($this->fetchToAPIFboFbs($url, $body, $header) as $unit) {
                foreach ($dataDB as $key => $item) {
                    if ($item['offer_id'] === $unit['offer_id']) {
                        $dataDB[$key]['price'] = $unit['price'];
                        $dataDB[$key]['fbo_sku'] = $unit['fbo_sku'];
                        $dataDB[$key]['fbs_sku'] = $unit['fbs_sku'];
                    }
                }
            }

            foreach (array_chunk($dataDB, 1000) as $chunk) {
                $this->repository->insertTable($table, $typeDB, $chunk);
            }
        } catch (Exception | ErrorException $e) {
            $journal->upTask('ERROR', $e->getMessage());
            outMsg($task, 'error.', $e->getMessage(), $this->debug);
            return;
        }

        cachePut($table, $project, formatDateTo(date('Y-m-d H:i:s'), 'Y-m-d H:i:s', 'UTC'), "{$task} : {$project}");

        $journal->upTask('OK');     // обновим запись в журнале

        unset($dataDB);
        unset($sz);
    }

    public function createUrl(string $urlAPI, ?string $task=''): string
    {
        if ($task === 'stock-warehouses') {
            return $urlAPI . "/v3/product/info/stocks";
        } elseif ($task === 'ListOfGoodsID') {
            return $urlAPI . "/v2/product/info/list";
        }
    }
}
