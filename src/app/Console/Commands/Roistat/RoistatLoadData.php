<?php

namespace App\Console\Commands\Roistat;

use App\Helpers\TypeTask;
use App\Jobs\RoistatProcess;
use App\Services\Export\SystemService;
use App\Services\Ozon\DataService;
use Illuminate\Console\Command;

class RoistatLoadData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roistat:data
                            {project_id? : id проекта}
                            {dateTo? : конечная граница запроса}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'выдача ответов по методу API - data';

    public function __construct(
        private SystemService $service = new SystemService(),
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
        if (is_null($dateTo)) {
            $dateTo = formatDateTo(date('Y-m-d H:i:s'), 'Y-m-d H:i:s', 'UTC');
        }

        $task = $this->service->getTaskOne($typeDB, $project_id, TypeTask::Data->value);
        if (is_null($task)) {
            $this->warn("Нет данных в настройках планировщика для [ Data ]");
            $this->newLine();
            return 1;
        }

        $table = $task->table;
        RoistatProcess::dispatchAfterResponse($table, $typeDB, $task->url, $project_id, $task->secret, $task->task, $task->dateFrom, dateTo: $dateTo);

        // (new DataService())->run($table, $typeDB, $task->url, $project_id, $task->secret, $task->task, $task->dateFrom, $dateTo);
    }
}