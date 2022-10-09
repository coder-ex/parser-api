<?php

namespace App\Console\Commands\Ozon;

use App\Helpers\TypeTask;
use App\Jobs\OZONProcess;
use App\Services\Export\SystemService;
use Illuminate\Console\Command;

class OzonLoadStockWarehouses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ozon:stock
                            {project_id? : id проекта}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'загрузка данных для Продажи';

    /**
     * Создать новый экземпляр команды
     */
    public function __construct(
        private SystemService $systemService = new SystemService(),
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
        $typeDB = env('TYPE_DB');

        $project_id = $this->argument('project_id');
        if (is_null($project_id)) {
            $this->warn('параметр [project_id] не задан / id проекта обязательно !!');
            $this->newLine();
            return 1;
        }

        $task = $this->systemService->getTaskOne($typeDB, $project_id, TypeTask::StockWarehouses->value);
        if (is_null($task)) {
            $this->error("Нет данных в настройках планировщика для [ StockWarehouses ]");
            $this->newLine();
            return 1;
        }

        $table = getApiParam($task->task, $task->name)['table'];
        OZONProcess::dispatchAfterResponse($table, $typeDB, $task->url, $project_id, $task->secret, $task->task, limit: json_decode($task['extended_fields'])->limit);

        //$this->endpoint->requestStockWarehouses($table, $typeDB, $task->url, $project_id, $task->secret, $task->task, json_decode($task['extended_fields'])->limit);

        $this->info("Команда завершена");
        $this->newLine();
        //---
        return 0;
    }
}
