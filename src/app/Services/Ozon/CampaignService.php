<?php

namespace App\Services\Ozon;

use App\Repositories\Export\ServiceRepository;
use App\Repositories\Ozon\OzonRepository;
use App\Services\Base\BaseService;
use App\Services\Base\InterfaceService;
use App\Services\Export\JournalService;
use App\Services\Ozon\Traits\FetchDataFromAPI;
use ErrorException;
use Exception;
use Illuminate\Support\Facades\DB;

class CampaignService extends BaseService implements InterfaceService
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
     * обработка по методу GET https://performance.ozon.ru/api/client/campaign
     *
     * @param string $table таблица выгрузки
     * @param string $typeDB тип соединения с БД (pgsql, mysql)
     * @param string $urlAPI шаблон url от API Ozon
     * @param string $project идентификатор проекта
     * @param string $secret ключ к кабинету клиента (key | token и т.д.)
     * @param string $task задача
     * @return void
     */
    public function run(string $table, string $typeDB, string $urlAPI, string $project, string $secret, string $task)
    {
        $sz = new Serializer($task, $this->debug);

        $journal = new JournalService($typeDB, $project, $task);
        $journal->startTask();

        try {
            $header = [
                'Content-Type' => 'application/json',
                'Accept' => '*/*',
                'Authorization' => 'Bearer ' . OzonToken::getToken($urlAPI, $project, $secret),
            ];

            $url = $this->createUrl($urlAPI, $secret, $project);

            $dataDB = [];
            foreach ($this->fetchToAPIGet($url, $header) as $unit) {
                $dataDB[] = $sz->serialize($unit, $project);
            }

            foreach (array_chunk($dataDB, 1000) as $unit) {
                $this->repository->insertTable($table, $typeDB, $unit);
            }

            //--- отработка по CampaignObject
            $service = new ServiceRepository();
            $name = $service->getProjects($typeDB, $project)->first()?->name;
            if (is_null($name)) {
                $journal->upTask('ERROR', 'нет записи в export_tasks');
                outMsg($task, 'error.', 'нет записи в export_tasks', $this->debug);
                return;
            }
            
            $arrCamp = DB::connection($typeDB)->table($table)->where('project_id', $project)->where('published_at', date('Y-m-d'))->get();
            $laoc = new CampaignObjectService();
            $laoc->run("ozon_{$name}_campaign_objects", $typeDB, $urlAPI, $project, $secret, 'objects_campaigns', $arrCamp);
        } catch (Exception | ErrorException $e) {
            $journal->upTask('ERROR', $e->getMessage());
            outMsg($task, 'error.', $e->getMessage(), $this->debug);
            return;
        }

        $journal->upTask('OK');     // обновим запись в журнале

        unset($dataDB);
        unset($sz);
        unset($arrCamp);
        unset($laoc);
    }

    public function createUrl(string $urlAPI, ?string $secret = '', ?string $project = ''): string
    {
        return "{$urlAPI}/api/client/campaign?client_secret={$secret}&client_id={$project}&advObjectType=SKU&state=CAMPAIGN_STATE_UNKNOWN";
    }
}
