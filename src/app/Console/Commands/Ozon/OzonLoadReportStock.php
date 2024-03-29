<?php

namespace App\Console\Commands\Ozon;

use App\Helpers\TypeTask;
use App\Jobs\OZONProcess;
use App\Services\Export\SystemService;
use App\Services\Ozon\RefWarehouseService;
use App\Services\Ozon\ReportStockService;
use Illuminate\Console\Command;

class OzonLoadReportStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ozon:report-stocks
                            {project_id? : id проекта}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'загрузка данных для FBO List - список отправлений';

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

        $task = $this->systemService->getTaskOne($typeDB, $project_id, TypeTask::ReportStocks->value);
        if (is_null($task)) {
            $this->error("Нет данных в настройках планировщика для [ ReportStocks ]");
            $this->newLine();
            return 1;
        }

        $table = $task->table;
        //OZONProcess::dispatchAfterResponse($table, $typeDB, $task->url, $project_id, $task->secret, $task->task);

        (new ReportStockService())->run($table, $typeDB, $task->url, $project_id, $task->secret, $task->task);
        (new RefWarehouseService())->run('', $typeDB, $task->url, $project_id, $task->secret, $task->task);

        $this->info("Команда завершена");
        $this->newLine();
        //---
        return 0;
    }
}
