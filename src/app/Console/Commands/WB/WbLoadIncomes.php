<?php

namespace App\Console\Commands\WB;

use App\Helpers\TypeTask;
use App\Jobs\WBProcess;
use App\Services\Export\SystemService;
use App\Services\WB\IncomesService;
use Illuminate\Console\Command;

class WbLoadIncomes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wb:incomes
                            {project_id? : id проекта}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'загрузка данных для Поставки';

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

        $task = $this->systemService->getTaskOne($typeDB, $project_id, TypeTask::Incomes->value);
        if (is_null($task)) {
            $this->error("Нет данных в настройках планировщика для [ Incomes ]");
            $this->newLine();
            return 1;
        }

        $table = $task->table;
        WBProcess::dispatchAfterResponse($table, $typeDB, $task->url, $task->project_id, $task->secret, $task->task, $task->dateFrom);

        // (new IncomesService())->run($table, $typeDB, $task->url, $task->project_id, $task->secret, $task->task, $task->dateFrom);

        $this->info("Команда завершена");
        $this->newLine();
        //---
        return 0;
    }
}
