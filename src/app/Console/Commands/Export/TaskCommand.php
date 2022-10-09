<?php

namespace App\Console\Commands\Export;

use App\Models\Ozon\OzonAquaFboList;
use App\Models\Ozon\OzonAquaStockWarehouse;
use App\Services\Export\SystemService;
use Illuminate\Console\Command;

class TaskCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:task
                            {api_name? : используемое api} 
                            {project_id? : id проекта}
                            {task? : задача в планировщик из TypeTask}
                            {dateFrom? : дата с которой забираем данные в формате: Y-m-d}
                            {startTime? : время старта задания в планировщике в формате H:i}
                            {flag? : флаг для задач orders и sales}
                            {limit? : лимит запроса за один раз в цикле, по умолчанию 10 000}
                            {url? : url адрес запроса к данным}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Создание задач для планировщика';

    /**
     * Создать новый экземпляр команды
     */
    public function __construct(
        private SystemService $systemService = new SystemService(),
        private $defaultIndex = null,
        private $maxAttempts = null,
        private bool $allowMultipleSelections = false
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $api_name = $this->choice(
            'выберите нужное api из списка',
            [
                'wb',
                'ozon',
                'ozon-performance',
                'roistat',
            ],
            $this->defaultIndex,
            $this->maxAttempts,
            $this->allowMultipleSelections
        );

        $project_id = $this->ask('project_id');
        $service = $this->systemService->getProjects(env('TYPE_DB'), $project_id);
        do {
            if (is_null($project_id)) {
                $this->warn('проект [project_id] не задан / проект обязательный !!');
                $this->newLine();
            } else {
                if ($service->count() == 0) {
                    $this->warn("[ проект: {$project_id} ]\nсервис по project_id не существует, создайте новый сервис");
                    $this->newLine();
                    return 1;
                }
                break;
            }

            $project_id = $this->ask('project_id');
        } while (true);

        $task = $this->choice(
            'выберите нужную задачу из списка',
            getArrayTask($api_name),
            $this->defaultIndex,
            $this->maxAttempts,
            $this->allowMultipleSelections
        );

        //--- проверим на дубли project+task
        if (!is_null($service)) {
            foreach ($service as $item) {
                $tasks = $item->tasks;

                foreach ($item->tasks as $unit) {
                    if ($unit->task === $task) {
                        $this->warn("[ проект: {$project_id} ] задача: {$task} уже создана в БД");
                        $this->newLine();
                        return 1;
                    }
                }
            }
        }

        $dateFrom = $this->ask('dateFrom : дата с которой забираем данные в формате: Y-m-d');
        do {
            if (!validateDate($dateFrom)) {
                $this->error('Не корректный формат даты [ ' . $dateFrom . ' ] / дата обязательна !!');
                $this->newLine();
            } else {
                break;
            }

            $dateFrom = $this->ask('dateFrom : дата с которой забираем данные в формате: Y-m-d');
        } while (true);

        $startTime = $this->ask('startTime : время старта задания в формате: H:i');
        do {
            if (!validateTime($startTime)) {
                $this->warn('Не корректный формат времени [ ' . $startTime . ' ] / время обязательно !!');
                $this->newLine();
            } else {
                break;
            }

            $startTime = $this->ask('startTime : время старта задания в формате: H:i');
        } while (true);

        $flag = $this->ask('flag : флаг для задач orders и sales');
        if (is_null($flag)) {
            $this->warn('параметр [flag] по умолчанию 0');
            $flag = 0;
        }

        $limit = $this->ask('limit : лимит запроса за один раз в цикле, по умолчанию 10 000');
        if (is_null($limit)) {
            $this->warn('параметр [limit] по умолчанию по умолчанию 10 000');
            $limit = 10000;
        }

        $url = $this->ask('url : url адрес запроса к данным');
        if (is_null($url)) {
            $this->error('параметр [url] ставим по умолчанию');
            if (getApiParam($task, $service->first()->name)['apiName'] === 'wb') {
                $url = 'https://suppliers-stats.wildberries.ru/api/v1/supplier';
            } elseif (getApiParam($task, $service->first()->name)['apiName'] === 'ozon') {
                $url = 'https://api-seller.ozon.ru';
            } elseif (getApiParam($task, $service->first()->name)['apiName'] === 'ozon-performance') {
                $url = 'https://performance.ozon.ru/api/client';
            } elseif (getApiParam($task, $service->first()->name)['apiName'] === 'roistat') {
                $url = 'https://cloud.roistat.com/api/v1';
            }
        }

        $this->info('процесс ввода параметров завершен');

        // запись параметров в БД
        $param[] = [
            'task' => $task,
            'dateFrom' => formatDateTo($dateFrom),
            'start_time' => $startTime,
            'extended_fields' => ($api_name === 'wb' || $api_name === 'ozon' || $api_name === 'roistat') ? json_encode(['flag' => $flag, 'limit' => $limit]) : null,
            'url' => $url,
            'table' => getApiParam($task, $service->first()->name)['table'],
            'service_id' => $service->first()->id
        ];

        $this->systemService->addTask(env('TYPE_DB'), $param);
        $task = $this->systemService->getTaskOne(env('TYPE_DB'), $project_id, $task);

        $this->table(
            [
                'task', 'dateFrom', 'start_time', 'extended_fields', 'url', 'table', 'service_id'
            ],
            [[$task->task, $task->dateFrom, $task->start_time, $task->extended_fields, $task->url, $task->class, $task->service_id]]
        );

        //---
        return 0;
    }
}
