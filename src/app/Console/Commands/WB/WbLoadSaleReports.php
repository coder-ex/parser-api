<?php

namespace App\Console\Commands\WB;

use App\Helpers\TypeTask;
use App\Jobs\WBProcess;
use App\Services\Export\SystemService;
use App\Services\WB\ReportSalesService;
use Illuminate\Console\Command;

class WbLoadSaleReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wb:sale-reports
                            {project_id? : id проекта}
                            {dateTo? : дата по которую забираем данные в формате: Y-m-d}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'загрузка данных для Отчет о продажах по реализации';

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

        $dateTo = $this->argument('dateTo');
        if (!is_null($dateTo) && !validateDate($dateTo)) {
            $this->error('Не корректный формат даты [ ' . $dateTo . ' ]');
            $this->newLine();
            return 1;
        }

        $task = $this->systemService->getTaskOne($typeDB, $project_id, TypeTask::SalesReports->value);
        if (is_null($task)) {
            $this->error("Нет данных в настройках планировщика для [ sale-reports ]");
            $this->newLine();
            return 1;
        }

        $table = $task->table;
        WBProcess::dispatchAfterResponse($table, $typeDB, $task->url, $task->project_id, $task->secret, $task->task, $task->dateFrom, dateTo: (is_null($dateTo)) ? date('Y-m-d') : $dateTo, limit: json_decode($task['extended_fields'])->limit);

        // (new ReportSalesService())->run($table, $typeDB, $task->url, $task->project_id, $task->secret, $task->task, $task->dateFrom, (is_null($dateTo)) ? date('Y-m-d') : $dateTo, json_decode($task['extended_fields'])->limit);

        $this->info("Команда завершена");
        $this->newLine();
        //---
        return 0;
    }
}
