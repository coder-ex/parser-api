<?php

namespace App\Services\Roistat;

use App\Repositories\Roistat\RoistatRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Export\JournalService;
use App\Services\Roistat\Traits\FetchDataFromAPI;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;

class UpOrderLIService extends BaseService implements InterfaceService
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
     * обновление данных по сделке в list_integrations
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url от API
     * @param string $project идентификатор проекта
     * @param string $secret key проекта 
     * @param string $task задача, в данном методе параметр не используется
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, ?string $task = null)
    {
        $journal = new JournalService($typeDB, $project, 'uporder-list-integrations');
        $journal->startTask();

        try {
            $header = [
                'Content-Type' => 'application/json',
                'Api-Key' => $secret
            ];

            $data = DB::connection($typeDB)->table($table)->where('project_id', $project)->get()->toArray();

            $count = 0;
            foreach ($data as $item) {
                $url = $this->createUrl($urlAPI, $project, $item->order_id);
                $order = $this->fetchToAPICost($url, $header);

                //--- !! WARNING: почему то получили null по json, временная мера, посмотрим что там творится
                if (is_null($order)) {
                    outMsg('uporder-list-integrations', 'WARNING.', 'число сделок [ ' . count($data) . ' ] число обработанных сделок [ ' . $count . ' ]', $this->debug);
                    break;
                }

                $item->client_id = $order['order']['client_id'] ?? null;
                $item->status_name = $order['order']['status_name'] ?? null;
                $item->fields_manager = $order['order']['fields_data']['Менеджер'] ?? null;
                $item->fields_in_prior = $order['order']['fields_data']['Входящий приоритет'] ?? null;
                $item->fields_work_prior = $order['order']['fields_data']['Рабочий приоритет'] ?? null;
                $item->fields_target_lead = $order['order']['fields_data']['Целевой лид'] ?? null;

                if (($count % 100) == 0) {
                    echo "размер массива по [status_name, fields_in_prior] == {$count}\n";
                }

                $count++;
            }

            echo "обновление данных в БД с новыми [status_name, fields_in_prior] == {$count}\n";

            foreach (array_chunk($data, 1000) as $chunk) {
                $this->repository->updateTable($table, $typeDB, $chunk);
            }
        } catch (Exception | ErrorException $e) {
            $journal->upTask('ERROR', $e->getMessage());
            outMsg('uporder-list-integrations', 'error.', $e->getMessage(), $this->debug);
            return;
        }

        $journal->upTask('OK');     // обновим запись в журнале
    }

    public function createUrl(string $urlAPI, ?string $project = null, ?string $orderId = null): string
    {
        return "{$urlAPI}/project/orders/{$orderId}/info?project={$project}";
    }
}
