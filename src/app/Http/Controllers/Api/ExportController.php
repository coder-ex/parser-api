<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Export\SystemService;
use App\Services\Ozon\Entities\CampaignEntity;
use App\Services\Ozon\Entities\CampaignExpenseEntity;
use App\Services\Ozon\Entities\CampaignFoodEntity;
use App\Services\Ozon\Entities\CampaignMediaEntity;
use App\Services\Ozon\Entities\CampaignObjectEntity;
use App\Services\Ozon\Entities\FboListEntity;
use App\Services\Ozon\Entities\ProductEntity;
use App\Services\Ozon\Entities\StatGetReportEntity;
use App\Services\Ozon\Entities\StatisticsDailyEntity;
use App\Services\Ozon\Entities\StockWarehouseEntity;
use App\Services\Roistat\Entities\CatalogMarkerEntity;
use App\Services\Roistat\Entities\DataEntity;
use App\Services\Roistat\Entities\ListIntegrationEntity;
use App\Services\WB\Entities\ExciseReportEntity;
use App\Services\WB\Entities\IncomeEntity;
use App\Services\WB\Entities\OrderEntity;
use App\Services\WB\Entities\SaleEntity;
use App\Services\WB\Entities\SaleReportEntity;
use App\Services\WB\Entities\StockEntity;
use Exception;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    private array $apiName = ['wb', 'ozon', 'ozon-performance', 'roistat'];

    public function __construct(
        private SystemService $service = new SystemService(),
        private string $typeDB = '',
    ) {
        $this->typeDB = env('TYPE_DB');
    }

    public function addServiceAll(Request $request)
    {
        try {
            $validated = $request->validate([
                'api_name' => 'required|string',
                'project_id' => 'required|string',
                'name' => 'required|string',
                'secret' => 'required|string',
                //'task' => 'required|string',
                'dateFrom' => 'required|date_format:Y-m-d',
                'start_time' => 'required|date_format:H:i',
                'extended_fields' => 'nullable|array',
            ]);

            //--- проверим на корректность api name
            $flag = false;
            foreach ($this->apiName as $item) {
                if ($validated['api_name'] === $item) {
                    $flag = true;
                    break;
                }
            }

            if (!$flag) {
                return response()->json(['message' => "[ api: {$validated['api_name']} ] в системе не зарегистрировано"], 200);
            }

            // запись параметров в БД
            $param = [
                'project_id' => $validated['project_id'],
                'name' => strtolower($validated['name']),   // переведем в нижний регистр весь name
                'secret' => $validated['secret']
            ];

            if (count($this->service->getProjects($this->typeDB, $validated['project_id'])) == 0) {
                //return response()->json(['message' => "[ проект: {$validated['project_id']} ] как сервис в БД уже есть"], 200);

                $this->service->addService($this->typeDB, [$param]);
                //$srv = $this->service->getServiceOne($typeDB, $param['secret']);
            }

            $tasks = [];
            foreach (getArrayTask($validated['api_name']) as $item) {
                $validated['task'] = $item;
                $tasks[] = $this->addTask($validated);
            }

            //--- создадим таблицы если их нет
            $this->addTable($validated['api_name'], $validated['name']);

            return response()->json(['success' => [...$tasks]], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), ...$this->description()], $e->getCode() == 0 ? $e->status : $e->getCode());
        }
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function addServiceOne(Request $request)
    {
        try {
            $validated = $request->validate([
                'api_name' => 'required|string',
                'project_id' => 'required|string',
                'name' => 'required|string',
                'secret' => 'required|string',
                'task' => 'required|string',
                'dateFrom' => 'required|date_format:Y-m-d',
                'start_time' => 'required|date_format:H:i',
                'extended_fields' => 'nullable|array',
            ]);

            // запись параметров в БД
            $param = [
                'project_id' => $validated['project_id'],
                'name' => strtolower($validated['name']),   // переведем в нижний регистр весь name
                'secret' => $validated['secret']
            ];

            if (count($this->service->getProjects($this->typeDB, $validated['project_id'])) == 0) {
                //return response()->json(['message' => "[ проект: {$validated['project_id']} ] как сервис в БД уже есть"], 200);

                $this->service->addService($this->typeDB, [$param]);
                //$srv = $this->service->getServiceOne($typeDB, $param['secret']);
            }

            $task = $this->addTask($validated);

            //--- создадим таблицы если их нет
            $this->addTable($validated['api_name'], $validated['name']);
            
            return response()->json(['success' => $task], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage(), ...$this->description()], $e->getCode() == 0 ? $e->status : $e->getCode());
        }
    }

    public function getHelp(string $api = null)
    {
        // $name = null;
        // if ($api === 'none') {
        //     foreach ($this->apiName as $el) {
        //         $name[] = [$el => getArrayTask($el)];
        //     }
        // }
        //---
        return response()->json(['success' => [...$this->description($api)]], 200);
    }

    private function addTask(array $validated)
    {
        try {
            $task = '';
            foreach (getArrayTask($validated['api_name']) as $item) {
                if ($item === $validated['task']) {
                    $task = $item;
                    break;
                }
            }

            if ($task === '') {
                return response()->json(['message' => "[ проект: {$validated['project_id']} ] параметр {$validated['task']} задан не корректно"], 200);
            }

            $projects = $this->service->getProjects($this->typeDB, $validated['project_id']);

            foreach ($projects as $item) {
                $tasks = $item->tasks;

                foreach ($item->tasks as $unit) {
                    if ($unit->task === $task) {
                        return response()->json(['message' => "[ проект: {$validated['project_id']} ] задача: {$task} уже создана в БД"], 200);
                    }
                }
            }

            //--- проверяется на уровне валидатора
            // if (!validateDate($validated['dateFrom'])) {
            //     return response()->json(['message' => 'Не корректный формат даты [ ' . $validated['dateFrom'] . ' ] / дата в формате Y-m-d !!'], 200);
            // }

            // if (!validateTime($validated['start_time'])) {
            //     return response()->json(['message' => 'Не корректный формат времени [ ' . $validated['start_time'] . ' ] / время в формате 00:00 !!'], 200);
            // }

            $url = null;
            if (getApiParam($task, $projects->first()->name)['apiName'] === 'wb') {
                $url = 'https://suppliers-stats.wildberries.ru/api/v1/supplier';
            } elseif (getApiParam($task, $projects->first()->name)['apiName'] === 'ozon') {
                $url = 'https://api-seller.ozon.ru';
            } elseif (getApiParam($task, $projects->first()->name)['apiName'] === 'ozon-performance') {
                $url = 'https://performance.ozon.ru';
            } elseif (getApiParam($task, $projects->first()->name)['apiName'] === 'roistat') {
                $url = 'https://cloud.roistat.com/api/v1';
            }

            $extended_fields = null;
            if (getApiParam($task, $projects->first()->name)['apiName'] !== 'ozon-performance') {
                $flag = $validated['extended_fields']['flag'] ?? null;
                $limit = $validated['extended_fields']['limit'] ?? null;
                if (!is_null($flag) || !is_null($limit)) {
                    $extended_fields = ($validated['api_name'] === 'wb' || $validated['api_name'] === 'ozon' || $validated['api_name'] === 'roistat') ? json_encode(['flag' => $flag ? $flag : 0, 'limit' => $limit ? $limit : 10000]) : null;
                }
            }

            // запись параметров в БД
            $data = [
                'task' => $task,
                'dateFrom' => formatDateTo($validated['dateFrom']),
                'start_time' => $validated['start_time'],
                'extended_fields' => $extended_fields,
                'url' => $url,
                'table' => getApiParam($task, $projects->first()->name)['table'],
                'service_id' => $projects->first()->id
            ];

            $this->service->addTask($this->typeDB, [$data]);

            return $this->service->getTaskOne($this->typeDB, $validated['project_id'], $task);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * вывод описания интерфейса
     *
     * @param string|null $apiName
     * @return array
     */
    private function description(string $apiName = null): array
    {
        $name = [];
        if (is_null($apiName)) {
            foreach ($this->apiName as $el) {
                $name[] = [$el => getArrayTask($el)];
            }
        } else {
            $name = getArrayTask($apiName);
        }

        return [
            'структура запроса для примера, поле extended_fields не обязательное:' => [
                'api_name' => 'название api (wb, ozon, roistat) предусмотренных в системе',
                'project_id' => 'идентификатор проекта, если отсутствует, то можно назначитиь по имени проекта',
                'name' => 'имя для префикса в таблицы',
                'secret' => 'secret/token/key',
                'task' => 'задача для регистрации, из списка задач',
                'dateFrom' => 'дата в формате Y-m-d',
                'start_time' => 'время для старта планировщика в формате H:i (24h)',
                'extended_fields' => [
                    'flag' => 0,
                    'limit' => 10000
                ],
            ],
            'поле task' => 'при массовом добавлении задачь по api, поле не требуется',
            'список задач в api:' => [
                $name,
            ],
            'поле extended_fields' => 'это массив значений, для wb например flag + limit',
        ];
    }

    private function addTable(string $apiName, string $name)
    {
        if ($apiName === 'wb') {
            $this->wbAddTable($name);
        } elseif ($apiName === 'ozon') {
            $this->ozonAddTable($name);
        } elseif ($apiName === 'ozon-performance') {
            $this->ozonPerformAddTable($name);
        } elseif ($apiName === 'roistat') {
            $this->roistatAddTable($name);
        }
    }

    private function ozonAddTable(string $name)
    {
        $entity = new FboListEntity($this->typeDB, $name, 'ozon', 'fbo_lists');                     // ставим впереди т.к. это связь с products
        $entity->up();
        $entity = new ProductEntity($this->typeDB, $name, 'ozon', 'products', 'fbo_lists');
        $entity->up();
        $entity = new StockWarehouseEntity($this->typeDB, $name, 'ozon', 'stock_warehouses');
        $entity->up();
        unset($entity);
    }

    private function ozonPerformAddTable(string $name)
    {
        $entity = new CampaignEntity($this->typeDB, $name, 'ozon', 'campaign');                     // ставим впереди т.к. это связь с objects_campaigns
        $entity->up();
        $entity = new CampaignObjectEntity($this->typeDB, $name, 'ozon','campaign_objects', 'campaign');
        $entity->up();
        $entity = new CampaignMediaEntity($this->typeDB, $name, 'ozon','campaign_media');
        $entity->up();
        $entity = new CampaignFoodEntity($this->typeDB, $name, 'ozon','campaign_foods');
        $entity->up();
        $entity = new CampaignExpenseEntity($this->typeDB, $name, 'ozon','campaign_expenses');
        $entity->up();
        $entity = new StatisticsDailyEntity($this->typeDB, $name, 'ozon', 'statistics_daily');
        $entity->up();
        $entity = new StatGetReportEntity($this->typeDB, $name, 'ozon', 'statistics_get_report');
        $entity->up();
        unset($entity);
    }

    private function roistatAddTable(string $name)
    {
        $entity = new CatalogMarkerEntity($this->typeDB, $name, 'roistat', 'catalog_markers');      // ставим впереди т.к. это связь сlist_integrations, data
        $entity->up();
        $entity = new ListIntegrationEntity($this->typeDB, $name, 'roistat', 'list_integrations', 'catalog_markers');
        $entity->up();
        $entity = new DataEntity($this->typeDB, $name, 'roistat', 'data', 'catalog_markers');
        $entity->up();
        unset($entity);
    }

    private function wbAddTable(string $name)
    {
        $entity = new IncomeEntity($this->typeDB, $name, 'wb', 'incomes');
        $entity->up();
        $entity = new StockEntity($this->typeDB, $name, 'wb', 'stocks');
        $entity->up();
        $entity = new OrderEntity($this->typeDB, $name, 'wb', 'orders');
        $entity->up();
        $entity = new SaleEntity($this->typeDB, $name, 'wb', 'sales');
        $entity->up();
        $entity = new SaleReportEntity($this->typeDB, $name, 'wb', 'sale_reports');
        $entity->up();
        $entity = new ExciseReportEntity($this->typeDB, $name, 'wb', 'excise_reports');
        $entity->up();
        unset($entity);
    }
}
