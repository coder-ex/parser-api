<?php

namespace App\Console\Commands\Ozon;

use App\Helpers\TypeTask;
use App\Jobs\OZONProcess;
use App\Services\Export\SystemService;
use Illuminate\Console\Command;

class OzonLoadStatDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ozon:stat-daily
                            {project_id? : id проекта}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'загрузка данных по дневной статистике в разрезе кампаний';

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

        $dateTo = formatDateTo(date('Y-m-d H:i:s'), 'Y-m-d\TH:i:s.v\Z', 'UTC');

        $task = $this->systemService->getTaskOne($typeDB, $project_id, TypeTask::StatDaily->value);
        if (is_null($task)) {
            $this->error("Нет данных в настройках планировщика для [ StatDaily ]");
            $this->newLine();
            return 1;
        }

        $table = $task->table;
        OZONProcess::dispatchAfterResponse($table, $typeDB, $task->url, $project_id, $task->secret, $task->task, $task->dateFrom, dateTo: $dateTo);

        //$this->endpoint->requestStatDaily($table, $typeDB, $task->url, $project_id, $task->secret, $task->task, $task->dateFrom, $dateTo);

        $this->info("Команда завершена");
        $this->newLine();
        //---
        return 0;
    }
}
